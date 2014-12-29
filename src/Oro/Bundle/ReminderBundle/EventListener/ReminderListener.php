<?php

namespace Oro\Bundle\ReminderBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager;
use Oro\Bundle\ReminderBundle\Entity\RemindableInterface;

class ReminderListener
{
    /**
     * @var ReminderManager
     */
    protected $reminderManager;

    /**
     * @param ReminderManager $reminderManager
     */
    public function __construct(ReminderManager $reminderManager)
    {
        $this->reminderManager = $reminderManager;
    }

    /**
     * After entity with reminders was loaded, load reminders
     *
     * @param LifecycleEventArgs $args
     */
    public function postLoad(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if ($entity instanceof RemindableInterface) {
            $this->reminderManager->loadReminders($entity);
        }
    }

    /**
     * Save reminders for new entities
     *
     * @param LifecycleEventArgs $event
     */
    public function postPersist(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();
        if (!$entity instanceof RemindableInterface) {
            return;
        }

        $this->reminderManager->saveReminders($entity);
    }
}
