<?php

namespace Oro\Bundle\UserBundle\Entity;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
use Oro\Bundle\ImapBundle\Manager\ImapEmailManager;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface as SecurityUserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

use Oro\Bundle\UserBundle\Entity\UserInterface as OroUserInterface;
use Zend\Mail\Storage\Folder;

class BaseUserManager implements UserProviderInterface
{
    /**
     * @var string
     */
    protected $class;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var EncoderFactoryInterface
     */
    protected $encoderFactory;

    /**
     * @var ImapEmailManager
     */
    protected $manager;

    protected $processedFolders = [];

    /**
     * Constructor
     *
     * @param string $class Entity name
     * @param ManagerRegistry $registry
     * @param EncoderFactoryInterface $encoderFactory
     * @param ImapConnectorFactory $connectorFactory
     * @param Mcrypt $mcrypt
     */
    public function __construct(
        $class,
        ManagerRegistry $registry,
        EncoderFactoryInterface $encoderFactory,
        ImapConnectorFactory $connectorFactory,
        MCrypt $mcrypt
    ) {
        $this->class = $class;
        $this->registry = $registry;
        $this->encoderFactory = $encoderFactory;
        $this->connectorFactory = $connectorFactory;
        $this->mcrypt = $mcrypt;
    }

    /**
     * Returns an empty user instance
     *
     * @return OroUserInterface
     */
    public function createUser()
    {
        $class = $this->getClass();

        return new $class;
    }

    /**
     * Updates a user
     *
     * @param  OroUserInterface $user
     * @param  bool $flush Whether to flush the changes (default true)
     */
    public function updateUser(OroUserInterface $user, $flush = true)
    {
        $this->assertRoles($user);
        $this->updatePassword($user);

        /** @var User $user */
        if ($user->getImapConfiguration() && $user->getImapConfiguration()->getRootFolders()) {
            $checkedFolders = $_REQUEST['oro_user_user_form']['imapConfiguration']['rootFolders'];
            $em = $this->registry->getManager();

            $origin = $user->getImapConfiguration();
            if (!$origin->getId()) {
                $em->persist($origin);
                $em->flush();
            }

            $config = new ImapConfig(
                $origin->getHost(),
                $origin->getPort(),
                $origin->getSsl(),
                $origin->getUser(),
                $this->mcrypt->decryptData($origin->getPassword())
            );

            $this->manager = new ImapEmailManager($this->connectorFactory->createImapConnector($config));
            $folders = $this->syncFolders($origin);
            foreach ($folders as $folder) {
                if ($this->hasFolder($folder->getFullName(), $checkedFolders)) {
                    $folder->setSyncEnabled(true);
                }

                if (!$folder->getId()) {
                    $em->persist($folder);
                }
            }

            $em->flush();
        }

        $this->getStorageManager()->persist($user);

        if ($flush) {
            $this->getStorageManager()->flush();
        }
    }

    /**
     * @param string $fullName
     * @param EmailFolder $folder
     *
     * @return bool
     */
    protected function hasFolder($fullName, $checkedFolders)
    {
        foreach ($checkedFolders as $checkedFolder) {
            if ($checkedFolder['fullName'] === $fullName) {
                if (isset($checkedFolder['syncEnabled']) && $checkedFolder['syncEnabled']) {
                    return true;
                }

                return false;
            }
        }

        return false;
    }

    /**
     * @param Folder[] $srcFolders
     *
     * @return EmailFolder[]
     */
    protected function processFolders(array $srcFolders, $origin)
    {
        $folders = [];
        foreach ($srcFolders as $srcFolder) {
            $folder = null;
            $folderFullName = $srcFolder->getGlobalName();
            $uidValidity = $this->getUidValidity($srcFolder);

            if ($uidValidity !== null) {
                $folder = $this->createFolder($srcFolder, $folderFullName, $origin);
                if ($folder === null) {
                    continue;
                }

                if (!$folder->getId()) {
                    $imapEmailFolder = new ImapEmailFolder();
                    $imapEmailFolder->setFolder($folder);
                    $imapEmailFolder->setUidValidity($uidValidity);

                    $this->registry->getManager()->persist($imapEmailFolder);
                }

                $folders[] = $folder;
            }

            $childSrcFolders = [];
            foreach ($srcFolder as $childSrcFolder) {
                $childSrcFolders[] = $childSrcFolder;
            }

            $childFolders = $this->processFolders($childSrcFolders, $origin);
            if (isset($folder)) {
                foreach ($childFolders as $childFolder) {
                    $folder->addSubFolder($childFolder);
                }
            } else {
                $folders = array_merge($folders, $childFolders);
            }
        }

        return $folders;
    }

    /**
     * Performs synchronization of folders
     *
     * @return EmailFolder[] The list of folders for which emails need to be synchronized
     */
    protected function syncFolders($origin)
    {
        //$existingImapFolders = $this->getExistingImapFolders($origin); // todo implement
        $srcFolders = $this->manager->getFolders(null, true);

        $this->processedFolders = [];

        return $this->processFolders($srcFolders, $origin);
    }

    protected function createFolder(Folder $srcFolder, $fullName, $origin)
    {
        if (in_array($fullName, $this->processedFolders)) {
            return null;
        }
        $em = $this->registry->getManager();
        $repo = $em->getRepository('OroEmailBundle:EmailFolder');
        $folder = $repo->findOneBy(['fullName' => $fullName, 'origin' => $origin]);

        if ($folder) {
            $this->processedFolders[] = $folder->getFullName();
            return $folder;
        }

        $folder = new EmailFolder();
        $folder
            ->setFullName($fullName)
            ->setOrigin($origin)
            ->setName($srcFolder->getLocalName())
            ->setType($srcFolder->guessFolderType());

        $this->processedFolders[] = $fullName;

        return $folder;
    }

    /**
     * Gets UIDVALIDITY of the given folder
     *
     * @param Folder $folder
     *
     * @return int|null
     */
    protected function getUidValidity(Folder $folder)
    {
        try {
            $this->manager->selectFolder($folder->getGlobalName());

            return $this->manager->getUidValidity();
        } catch (\Exception $e) {
            return null;
        }
    }



    /**
     * Updates a user password if a plain password is set
     *
     * @param OroUserInterface $user
     */
    public function updatePassword(OroUserInterface $user)
    {
        $password = $user->getPlainPassword();
        if (0 !== strlen($password)) {
            $encoder = $this->getEncoder($user);

            $user->setPassword($encoder->encodePassword($password, $user->getSalt()));
            $user->eraseCredentials();
        }
    }

    /**
     * Deletes a user
     *
     * @param object $user
     */
    public function deleteUser($user)
    {
        $this->getStorageManager()->remove($user);
        $this->getStorageManager()->flush();
    }

    /**
     * Finds one user by the given criteria
     *
     * @param  array $criteria
     * @return OroUserInterface
     */
    public function findUserBy(array $criteria)
    {
        return $this->getRepository()->findOneBy($criteria);
    }

    /**
     * Returns a collection with all user instances
     *
     * @return \Traversable
     */
    public function findUsers()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Finds a user by email
     *
     * @param  string $email
     * @return OroUserInterface
     */
    public function findUserByEmail($email)
    {
        return $this->findUserBy(['email' => $email]);
    }

    /**
     * Finds a user by username
     *
     * @param  string $username
     * @return OroUserInterface
     */
    public function findUserByUsername($username)
    {
        return $this->findUserBy(['username' => $username]);
    }

    /**
     * Finds a user either by email, or username
     *
     * @param  string $usernameOrEmail
     * @return OroUserInterface
     */
    public function findUserByUsernameOrEmail($usernameOrEmail)
    {
        if (filter_var($usernameOrEmail, FILTER_VALIDATE_EMAIL)) {
            return $this->findUserByEmail($usernameOrEmail);
        }

        return $this->findUserByUsername($usernameOrEmail);
    }

    /**
     * Finds a user either by confirmation token
     *
     * @param  string $token
     * @return OroUserInterface
     */
    public function findUserByConfirmationToken($token)
    {
        return $this->findUserBy(['confirmationToken' => $token]);
    }

    /**
     * Reloads a user
     *
     * @param object $user
     */
    public function reloadUser($user)
    {
        $this->getStorageManager()->refresh($user);
    }

    /**
     * Refreshed a user by User Instance
     *
     * It is strongly discouraged to use this method manually as it bypasses
     * all ACL checks.
     *
     * @param  SecurityUserInterface $user
     * @return OroUserInterface
     * @throws UnsupportedUserException if a User Instance is given which is not managed by this UserManager
     *                                  (so another Manager could try managing it)
     * @throws UsernameNotFoundException if user could not be reloaded
     */
    public function refreshUser(SecurityUserInterface $user)
    {
        $class = $this->getClass();

        if (!$user instanceof $class) {
            throw new UnsupportedUserException('Account is not supported');
        }

        if (!$user instanceof OroUserInterface) {
            throw new UnsupportedUserException(
                sprintf(
                    'Expected an instance of Oro\Bundle\UserBundle\Entity\UserInterface, but got "%s"',
                    get_class($user)
                )
            );
        }

        return $this->findUserByUsername($user->getUsername());
    }

    /**
     * Loads a user by username.
     * It is strongly discouraged to call this method manually as it bypasses
     * all ACL checks.
     *
     * @param  string $username
     * @return OroUserInterface
     * @throws UsernameNotFoundException if user not found
     */
    public function loadUserByUsername($username)
    {
        $user = $this->findUserByUsername($username);

        if (!$user) {
            throw new UsernameNotFoundException(sprintf('No user with name "%s" was found.', $username));
        }

        return $user;
    }

    /**
     * Returns the user's fully qualified class name.
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsClass($class)
    {
        return $class === $this->getClass();
    }

    /**
     * @param OroUserInterface|string $user
     * @return PasswordEncoderInterface
     */
    protected function getEncoder($user)
    {
        return $this->encoderFactory->getEncoder($user);
    }

    /**
     * Returns basic query instance to get collection with all user instances
     *
     * @return QueryBuilder
     */
    public function getListQuery()
    {
        return $this->getStorageManager()
            ->createQueryBuilder()
            ->select('u')
            ->from($this->getClass(), 'u')
            ->orderBy('u.id', 'ASC');
    }

    /**
     * Return related repository
     *
     * @return ObjectRepository
     */
    public function getRepository()
    {
        return $this->getStorageManager()->getRepository($this->getClass());
    }

    /**
     * @return ObjectManager|EntityManager
     */
    public function getStorageManager()
    {
        return $this->registry->getManagerForClass($this->getClass());
    }

    /**
     * We need to make sure to have at least one role.
     *
     * @param UserInterface $user
     * @throws \RuntimeException
     */
    protected function assertRoles(UserInterface $user)
    {
        if (count($user->getRoles()) === 0) {
            throw new \RuntimeException('User has not default role');
        }
    }
}
