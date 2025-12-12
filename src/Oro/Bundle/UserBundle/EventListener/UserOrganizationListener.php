<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * User entity listener for adding created or updated user to organization
 * when user created not from backend UI or API (by console, custom import etc.)
 */
class UserOrganizationListener
{
    public function __construct(
        private TokenAccessorInterface $tokenAccessor
    ) {
    }

    public function preUpdate(User $user): void
    {
        $this->addUserToOrganization($user);
    }

    public function prePersist(User $user): void
    {
        $this->addUserToOrganization($user);
    }

    private function addUserToOrganization(User $user): void
    {
        if ($user->getOrganizations()->count() !== 0) {
            return;
        }

        $userOrganization = $user->getOrganization() ?? $this->tokenAccessor->getOrganization();

        if ($userOrganization) {
            $user->addOrganization($userOrganization);
        }
    }
}
