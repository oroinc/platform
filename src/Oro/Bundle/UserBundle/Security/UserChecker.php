<?php

namespace Oro\Bundle\UserBundle\Security;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Exception\CredentialsResetException;
use Oro\Bundle\UserBundle\Exception\EmptyOwnerException;
use Oro\Bundle\UserBundle\Exception\OrganizationException;
use Oro\Bundle\UserBundle\Exception\PasswordChangedException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;

/**
 * Checks the state of User during authentication.
 */
class UserChecker implements UserCheckerInterface
{
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function checkPostAuth(SymfonyUserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isEnabled()) {
            $exception = new DisabledException('The user is disabled.');
            $exception->setUser($user);

            throw $exception;
        }
        if (null !== $user->getAuthStatus() && $user->getOrganizations(true)->count() === 0) {
            $exception = new OrganizationException();
            $exception->setUser($user);

            throw $exception;
        }
        if (!$user->getOwner()) {
            $exception = new EmptyOwnerException('The user does not have an owner.');
            $exception->setUser($user);

            throw $exception;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function checkPreAuth(SymfonyUserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (null !== $this->tokenStorage->getToken()
            && null !== $user->getPasswordChangedAt()
            && null !== $user->getLastLogin()
            && $user->getPasswordChangedAt() > $user->getLastLogin()
        ) {
            $exception = new PasswordChangedException('Invalid password.');
            $exception->setUser($user);

            throw $exception;
        }

        if ($user->getAuthStatus() && $user->getAuthStatus()->getId() !== UserManager::STATUS_ACTIVE) {
            $exception = new CredentialsResetException('Password reset.');
            $exception->setUser($user);

            throw $exception;
        }
    }
}
