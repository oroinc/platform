<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PasswordChangedSubscriber implements EventSubscriber
{
    /** @var ContainerInterface */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
     * Change 'expired' status to 'active'
     *
     * @param  LifecycleEventArgs $args
     */
    private function updateAuthStatus(LifecycleEventArgs $args)
    {
        $user = $args->getEntity();
        if (!$user instanceof User) {
            return;
        }

        if ($user->getAuthStatus() && $user->getAuthStatus()->getId() === UserManager::STATUS_EXPIRED) {
            $user->setAuthStatus(
                $this->getEnumValueProvider()->getEnumValueByCode('auth_status', UserManager::STATUS_ACTIVE)
            );
        }
    }

    /**
     * @return EnumValueProvider
     */
    private function getEnumValueProvider()
    {
        return $this->container->get('oro_entity_extend.enum_value_provider');
    }
}
