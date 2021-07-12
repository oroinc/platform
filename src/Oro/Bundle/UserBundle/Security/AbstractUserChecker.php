<?php

namespace Oro\Bundle\UserBundle\Security;

use Oro\Bundle\UserBundle\Entity\UserInterface;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\Exception\LockedException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;

/**
 * Base class for UserChecker.
 */
class AbstractUserChecker implements UserCheckerInterface
{
    /**
     * {@inheritdoc}
     */
    public function checkPreAuth(SymfonyUserInterface $user): void
    {
        if (!$user instanceof UserInterface) {
            return;
        }

        if (!$user->isAccountNonLocked()) {
            $ex = new LockedException('User account is locked.');
            $ex->setUser($user);
            throw $ex;
        }

        if (!$user->isEnabled()) {
            $ex = new DisabledException('User account is disabled.');
            $ex->setUser($user);
            throw $ex;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function checkPostAuth(SymfonyUserInterface $user): void
    {
    }
}
