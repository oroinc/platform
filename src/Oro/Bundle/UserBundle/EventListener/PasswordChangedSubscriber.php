<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

/**
 * Changes auth status on preupdate
 */
class PasswordChangedSubscriber implements EventSubscriber
{
    /** @var EnumValueProvider */
    private $enumValueProvider;

    public function __construct(EnumValueProvider $enumValueProvider)
    {
        $this->enumValueProvider = $enumValueProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $this->updateAuthStatus($args);
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        if ($args->hasChangedField('password')) {
            $this->updateAuthStatus($args);
        }
    }

    /**
     * Change 'expired' status to 'active'
     */
    private function updateAuthStatus(LifecycleEventArgs $args)
    {
        $user = $args->getEntity();
        if (!$user instanceof User) {
            return;
        }

        if ($user->getAuthStatus() && $user->getAuthStatus()->getId() === UserManager::STATUS_EXPIRED) {
            $user->setAuthStatus(
                $this->enumValueProvider->getEnumValueByCode('auth_status', UserManager::STATUS_ACTIVE)
            );
        }
    }
}
