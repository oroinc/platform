<?php

namespace Oro\Bundle\UserBundle\Provider;

use Oro\Bundle\UserBundle\Entity\User;

/**
 * This provider provides basic user info for logging purposes
 */
class UserLoggingInfoProvider
{
    /**
     * @param User $user
     * @return array
     */
    public function getUserLoggingInfo(User $user)
    {
        return [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'fullname' => $user->getFullName(),
            'enabled' => $user->isEnabled(),
            'lastlogin' => $user->getLastLogin(),
            'createdat' => $user->getCreatedAt(),
        ];
    }
}
