<?php

namespace Oro\Bundle\UserBundle\Entity;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMInvalidArgumentException;
use Oro\Bundle\UserBundle\Entity\Repository\AbstractUserRepository;
use Oro\Bundle\UserBundle\Entity\UserInterface as OroUserInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface as SecurityUserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * The base class for work with a user entity.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BaseUserManager implements UserProviderInterface
{
    /** @var string */
    protected $class;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var EncoderFactoryInterface */
    protected $encoderFactory;

    /**
     * @param string                  $class Entity name
     * @param ManagerRegistry         $registry
     * @param EncoderFactoryInterface $encoderFactory
     */
    public function __construct(
        $class,
        ManagerRegistry $registry,
        EncoderFactoryInterface $encoderFactory
    ) {
        $this->class = $class;
        $this->registry = $registry;
        $this->encoderFactory = $encoderFactory;
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
     * @param  bool             $flush Whether to flush the changes (default true)
     */
    public function updateUser(OroUserInterface $user, $flush = true)
    {
        $this->assertRoles($user);
        $this->updatePassword($user);

        $storageManager = $this->getStorageManager();
        $storageManager->persist($user);
        if ($flush) {
            $storageManager->flush();
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
        $storageManager = $this->getStorageManager();
        $storageManager->remove($user);
        $storageManager->flush();
    }

    /**
     * Finds one user by the given criteria
     *
     * @param  array $criteria
     *
     * @return OroUserInterface
     */
    public function findUserBy(array $criteria)
    {
        return $this->getRepository()->findOneBy($criteria);
    }

    /**
     * Finds a user by email
     *
     * @param  string $email
     *
     * @return OroUserInterface
     */
    public function findUserByEmail($email)
    {
        $repository = $this->getRepository();

        // Check if repository supports case insensitive search by email.
        if ($repository instanceof AbstractUserRepository) {
            return $repository->findUserByEmail((string)$email, $this->isCaseInsensitiveEmailAddressesEnabled());
        }

        return $this->findUserBy(['email' => $email]);
    }

    /**
     * @return bool
     */
    protected function isCaseInsensitiveEmailAddressesEnabled(): bool
    {
        return false;
    }

    /**
     * Finds a user by username
     *
     * @param  string $username
     *
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
     *
     * @return OroUserInterface
     */
    public function findUserByUsernameOrEmail($usernameOrEmail)
    {
        $user = $this->findUserByUsername($usernameOrEmail);
        if (!$user && filter_var($usernameOrEmail, FILTER_VALIDATE_EMAIL)) {
            $user = $this->findUserByEmail($usernameOrEmail);
        }

        return $user;
    }

    /**
     * Finds a user either by confirmation token
     *
     * @param  string $token
     *
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
     *
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
                sprintf('Expected an instance of %s, but got "%s"', OroUserInterface::class, get_class($user))
            );
        }

        // Refresh user should revert entity back to it's initial state using non changed field
        // Otherwise entity may be replaced with another as some field may be changed in memory
        // Example: user changed username, change was rejected by validation but in memory value was changed
        // Calling to refreshUser and using username as criteria will lead to user replacing with another user
        // UoW has internal identity cache which will not actually reload user just by calling to findOneBy
        try {
            // Try to reload existing entity to revert it's state to initial
            $this->reloadUser($user);
        } catch (ORMInvalidArgumentException $e) {
            // If entity is not managed and can not be reloaded - load it by ID from database
            $user = $this->getRepository()->find($user->getId());
        }

        if (!$user instanceof UserInterface) {
            throw new UsernameNotFoundException('User can not be loaded.');
        }

        return $user;
    }

    /**
     * Loads a user by username.
     * It is strongly discouraged to call this method manually as it bypasses
     * all ACL checks.
     *
     * @param  string $username
     *
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
     *
     * @return PasswordEncoderInterface
     */
    protected function getEncoder($user)
    {
        return $this->encoderFactory->getEncoder($user);
    }

    /**
     * Return related repository
     *
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->getStorageManager()->getRepository($this->getClass());
    }

    /**
     * @return EntityManager
     */
    public function getStorageManager()
    {
        return $this->registry->getManagerForClass($this->getClass());
    }

    /**
     * We need to make sure to have at least one role.
     *
     * @param UserInterface $user
     *
     * @throws \RuntimeException
     */
    protected function assertRoles(UserInterface $user)
    {
        if (count($user->getRoles()) === 0) {
            throw new \RuntimeException('User has not default role');
        }
    }
}
