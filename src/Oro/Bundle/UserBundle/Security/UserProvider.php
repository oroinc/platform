<?php

namespace Oro\Bundle\UserBundle\Security;

use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
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

    #[\Override]
    public function loadUserByIdentifier(string $username): UserInterface
    {
        $user = $this->userLoader->loadUser($username);
        if (null === $user) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $username));
        }

        return $user;
    }

    #[\Override]
    public function refreshUser(UserInterface $user): UserInterface
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
            throw new UserNotFoundException('User can not be loaded.');
        }

        return $user;
    }

    #[\Override]
    public function supportsClass($class): bool
    {
        return is_a($class, $this->userLoader->getUserClass(), true);
    }
}
