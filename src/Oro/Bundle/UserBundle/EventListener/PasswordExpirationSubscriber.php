<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\PasswordChangePeriodConfigProvider;

class PasswordExpirationSubscriber implements EventSubscriber
{
    /** @var PasswordChangePeriodConfigProvider */
    protected $provider;

    /**
     * @param PasswordChangePeriodConfigProvider $provider
     */
    public function __construct(PasswordChangePeriodConfigProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'prePersist',
            'preUpdate',
        );
    }

    /**
     * @param  LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $this->resetUserPasswordExpiryDate($args);
    }

    /**
     * @param  PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        if ($args->hasChangedField('password')) {
            $this->resetUserPasswordExpiryDate($args);
        }
    }

    /**
     * @param  LifecycleEventArgs $args
     */
    protected function resetUserPasswordExpiryDate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if (!$entity instanceof User) {
            return;
        }

        $entity->setPasswordExpiresAt($this->provider->getPasswordExpiryDateFromNow());
    }
}
