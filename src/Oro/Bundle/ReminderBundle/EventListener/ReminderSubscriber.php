<?php

namespace Oro\Bundle\ReminderBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager;
use Oro\Bundle\ReminderBundle\Entity\RemindableInterface;

class ReminderSubscriber implements EventSubscriber
{
    /**
     * @var ContainerInterface
     */
    protected $container;

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
        return array(
            // @codingStandardsIgnoreStart
            Events::postLoad
            // @codingStandardsIgnoreEnd
        );
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
            $this->getReminderManager()->loadReminders($entity);
        }
    }

    /**
     * @return ReminderManager
     */
    protected function getReminderManager()
    {
        return $this->container->get('oro_reminder.entity.manager');
    }
}
