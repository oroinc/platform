<?php

namespace Oro\Bundle\UserBundle\Security;

use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Loads user entity from the database for the authentication system.
 */
class UserProvider implements UserProviderInterface
{
    /** @var UserLoaderInterface */
    private $userLoader;

    /** @var ManagerRegistry */
    private $doctrine;

    public function __construct(UserLoaderInterface $userLoader, ManagerRegistry $doctrine)
    {
        $this->userLoader = $userLoader;
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username)
    {
        $user = $this->userLoader->loadUser($username);
        if (null === $user) {
            throw new UsernameNotFoundException(sprintf('User "%s" not found.', $username));
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user)
    {
        $userClass = $this->userLoader->getUserClass();
        if (!$user instanceof $userClass) {
            throw new UnsupportedUserException(sprintf(
                'Expected an instance of %s, but got "%s".',
                $userClass,
                get_class($user)
            ));
        }

        $manager = $this->doctrine->getManagerForClass($userClass);
        if (null === $manager) {
            throw new \LogicException(sprintf('The "%s" must be manageable entity.', $userClass));
        }

        // Refresh user should revert entity back to it's initial state using non changed field;
        // otherwise, entity may be replaced with another as some field may be changed in memory.
        // Example: a user changed username, the change was rejected by validation but in memory value was changed.
        // Calling to refreshUser and using username as criteria will lead to user replacing with another user.
        // UoW has internal identity cache which will not actually reload user just by calling to findOneBy.
        try {
            // try to reload existing entity to revert it's state to initial
            $manager->refresh($user);
        } catch (ORMInvalidArgumentException $e) {
            // if entity is not managed and can not be reloaded - load it by ID from the database
            $user = $manager->find($userClass, $user->getId());
        }

        if (null === $user) {
            throw new UsernameNotFoundException('User can not be loaded.');
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return is_a($class, $this->userLoader->getUserClass(), true);
    }
}
