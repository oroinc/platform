<?php

namespace Oro\Bundle\ReminderBundle\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager;
use Oro\Bundle\ReminderBundle\Entity\RemindableInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Handles entities that implements RemindableInterface.
 */
class ReminderListener implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ReminderManager::class
        ];
    }

    /**
     * After entity with reminders was loaded, load reminders
     */
    public function postLoad(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof RemindableInterface) {
            $this->getReminderManager()->loadReminders($entity);
        }
    }

    /**
     * Save reminders for new entities
     */
    public function postPersist(LifecycleEventArgs $event): void
    {
        $entity = $event->getObject();
        if ($entity instanceof RemindableInterface) {
            $this->getReminderManager()->saveReminders($entity);
        }
    }

    private function getReminderManager(): ReminderManager
    {
        return $this->container->get(ReminderManager::class);
    }
}
