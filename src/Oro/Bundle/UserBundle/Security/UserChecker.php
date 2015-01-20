<?php

namespace Oro\Bundle\UserBundle\Security;

use Symfony\Component\Security\Core\User\UserChecker as BaseUserChecker;
use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Exception\PasswordChangedException;

class UserChecker extends BaseUserChecker
{
    public function checkPreAuth(UserInterface $user)
    {
        parent::checkPreAuth($user);

        if ($user instanceof User) {
            if ($user->getPasswordChangedAt() != null && $user->getPasswordChangedAt() > $user->getLastLogin()) {
                $ex = new PasswordChangedException(
                    'Your password has been updated by administrator. New password was sent to you by email.'
                );
                $ex->setUser($user);
                throw $ex;
            }
        }
    }
}
