<?php

namespace Oro\Bundle\UserBundle\Entity;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface as SecurityUserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

use Oro\Bundle\UserBundle\Entity\UserInterface as OroUserInterface;

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
     * Constructor
     *
     * @param string $class Entity name
     * @param ManagerRegistry $registry
     * @param EncoderFactoryInterface $encoderFactory
     */
    public function __construct($class, ManagerRegistry $registry, EncoderFactoryInterface $encoderFactory)
    {
        $this->class = $class;
        $this->registry = $registry;
        $this->encoderFactory = $encoderFactory;
    }

    /**
     * Returns an empty user instance
     *
     * @return User
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
        $this->getStorageManager()->persist($user);

        if ($flush) {
            $this->getStorageManager()->flush();
        }
    }

    /**
     * Updates a user password if a plain password is set
     *
     * @param OroUserInterface $user
     */
    public function updatePassword(OroUserInterface $user)
    {
        if (0 !== strlen($password = $user->getPlainPassword())) {
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
     * @return User
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
     * @return User
     */
    public function findUserByEmail($email)
    {
        return $this->findUserBy(['email' => $email]);
    }

    /**
     * Finds a user by username
     *
     * @param  string $username
     * @return User
     */
    public function findUserByUsername($username)
    {
        return $this->findUserBy(['username' => $username]);
    }

    /**
     * Finds a user either by email, or username
     *
     * @param  string $usernameOrEmail
     * @return User
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
     * @return User
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
     * @return User
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
     * @return User
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
     * @return ObjectManager
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
    protected function assertRoles(OroUserInterface $user)
    {
        if (count($user->getRoles()) === 0) {
            $role = $this->getStorageManager()
                ->getRepository('OroUserBundle:Role')
                ->findOneBy(['role' => User::ROLE_DEFAULT]);

            if (!$role) {
                throw new \RuntimeException('Default user role not found');
            }

            $user->addRole($role);
        }
    }
}
