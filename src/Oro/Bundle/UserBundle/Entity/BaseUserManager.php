<?php

namespace Oro\Bundle\UserBundle\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\UserBundle\Security\UserLoaderInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

/**
 * The base class for work with a user entity.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BaseUserManager
{
    /** @var UserLoaderInterface */
    private $userLoader;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var EncoderFactoryInterface */
    private $encoderFactory;

    public function __construct(
        UserLoaderInterface $userLoader,
        ManagerRegistry $doctrine,
        EncoderFactoryInterface $encoderFactory
    ) {
        $this->userLoader = $userLoader;
        $this->doctrine = $doctrine;
        $this->encoderFactory = $encoderFactory;
    }

    /**
     * Returns an empty user instance
     */
    public function createUser(): UserInterface
    {
        $class = $this->userLoader->getUserClass();

        return new $class;
    }

    /**
     * Updates a user
     *
     * @param UserInterface $user
     * @param bool          $flush Whether to flush the changes (default true)
     */
    public function updateUser(UserInterface $user, bool $flush = true): void
    {
        $this->updatePassword($user);

        $em = $this->getEntityManager();
        $em->persist($user);
        if ($flush) {
            $em->flush();
        }
    }

    /**
     * Updates a user password if a plain password is set
     */
    public function updatePassword(UserInterface $user): void
    {
        $password = $user->getPlainPassword();
        if ($password !== null && 0 !== strlen($password)) {
            $encoder = $this->encoderFactory->getEncoder($user);
            $user->setPassword($encoder->encodePassword($password, $user->getSalt()));
            $user->eraseCredentials();
        }
    }

    /**
     * Generates a random string that can be used as a password for a user.
     */
    public function generatePassword(int $maxLength = 30): string
    {
        return str_shuffle(
            substr(
                sprintf(
                    '%s%s%s',
                    // get one random upper case letter
                    substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1),
                    // get one random digit
                    substr(str_shuffle('1234567890'), 0, 1),
                    // get some random string
                    $this->generateToken()
                ),
                0,
                $maxLength
            )
        );
    }

    /**
     * Deletes a user
     */
    public function deleteUser(UserInterface $user): void
    {
        $em = $this->getEntityManager();
        $em->remove($user);
        $em->flush();
    }

    /**
     * Finds a user by the given criteria
     */
    public function findUserBy(array $criteria): ?UserInterface
    {
        return $this->getRepository()->findOneBy($criteria);
    }

    /**
     * Finds a user by email
     */
    public function findUserByEmail(string $email): ?UserInterface
    {
        return $this->userLoader->loadUserByEmail($email);
    }

    /**
     * Finds a user by username
     */
    public function findUserByUsername(string $username): ?UserInterface
    {
        return $this->userLoader->loadUserByUsername($username);
    }

    /**
     * Finds a user either by email or username
     */
    public function findUserByUsernameOrEmail(string $usernameOrEmail): ?UserInterface
    {
        return $this->userLoader->loadUser($usernameOrEmail);
    }

    /**
     * Finds a user by confirmation token
     */
    public function findUserByConfirmationToken(string $token): ?UserInterface
    {
        return $this->findUserBy(['confirmationToken' => $token]);
    }

    /**
     * Reloads a user
     */
    public function reloadUser(UserInterface $user): void
    {
        $this->getEntityManager()->refresh($user);
    }

    /**
     * Returns user repository
     */
    protected function getRepository(): EntityRepository
    {
        return $this->getEntityManager()->getRepository($this->userLoader->getUserClass());
    }

    /**
     * Returns user entity manager
     */
    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrine->getManagerForClass($this->userLoader->getUserClass());
    }

    protected function generateToken(): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', uniqid(mt_rand(), true), true)), '+/', '-_'), '=');
    }
}
