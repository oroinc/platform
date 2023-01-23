<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

/**
 * Changes auth status from to "active" for newly created users
 * and when password changed for existing users.
 */
class PasswordChangedListener
{
    private EnumValueProvider $enumValueProvider;

    public function __construct(EnumValueProvider $enumValueProvider)
    {
        $this->enumValueProvider = $enumValueProvider;
    }

    public function prePersist(User $user): void
    {
        $this->updateAuthStatus($user);
    }

    public function preUpdate(User $user, PreUpdateEventArgs $eventArgs): void
    {
        if ($eventArgs->hasChangedField('password')) {
            $this->updateAuthStatus($user);
        }
    }

    private function updateAuthStatus(User $user): void
    {
        if ($user->getAuthStatus() && $user->getAuthStatus()->getId() !== UserManager::STATUS_ACTIVE) {
            $user->setAuthStatus(
                $this->enumValueProvider->getEnumValueByCode('auth_status', UserManager::STATUS_ACTIVE)
            );
        }
    }
}
