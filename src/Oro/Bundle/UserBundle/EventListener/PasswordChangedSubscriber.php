<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\UserBundle\Entity\User;

class PasswordChangedSubscriber implements EventSubscriber
{
    const STATUS_AVAILABLE = 'available';
    const STATUS_EXPIRED = 'expired';

    /** @var EnumValueProvider */
    protected $enumValueProvider;

    /**
     * @param EnumValueProvider $enumValueProvider
     */
    public function __construct(EnumValueProvider $enumValueProvider)
    {
        $this->enumValueProvider = $enumValueProvider;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            'prePersist',
            'preUpdate',
        ];
    }

    /**
     * @param  LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $this->updateAuthStatus($args);
    }

    /**
     * @param  PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        if ($args->hasChangedField('password')) {
            $this->updateAuthStatus($args);
        }
    }

    /**
     * Change 'expired' status to 'available'
     *
     * @param  LifecycleEventArgs $args
     */
    protected function updateAuthStatus(LifecycleEventArgs $args)
    {
        $user = $args->getEntity();
        if (!$user instanceof User) {
            return;
        }

        if ($user->getAuthStatus() && $user->getAuthStatus()->getId() === self::STATUS_EXPIRED) {
            $user->setAuthStatus($this->enumValueProvider->getEnumValueByCode('auth_status', self::STATUS_AVAILABLE));
        }
    }
}
