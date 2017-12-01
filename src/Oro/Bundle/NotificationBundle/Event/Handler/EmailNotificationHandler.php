<?php

namespace Oro\Bundle\NotificationBundle\Event\Handler;

use Doctrine\ORM\EntityManager;

use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\NotificationBundle\Event\NotificationEvent;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;

class EmailNotificationHandler implements EventHandlerInterface
{
    /** @var EmailNotificationManager */
    protected $manager;

    /** @var EntityManager */
    protected $em;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /**
     * @param EmailNotificationManager $manager
     * @param EntityManager $em
     * @param PropertyAccessor $propertyAccessor
     */
    public function __construct(
        EmailNotificationManager $manager,
        EntityManager $em,
        PropertyAccessor $propertyAccessor
    ) {
        $this->manager = $manager;
        $this->em = $em;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(NotificationEvent $event, $matchedNotifications)
    {
        // convert notification rules to a list of EmailNotificationInterface
        $notifications = [];
        foreach ($matchedNotifications as $notification) {
            $notifications[] = $this->getEmailNotificationAdapter($event, $notification);
        }

        // send notifications
        $this->manager->process($event->getEntity(), $notifications);
    }

    /**
     * @param NotificationEvent $event
     * @param EmailNotification $notification
     *
     * @return EmailNotificationAdapter
     */
    protected function getEmailNotificationAdapter(NotificationEvent $event, EmailNotification $notification)
    {
        return new EmailNotificationAdapter(
            $event->getEntity(),
            $notification,
            $this->em,
            $this->propertyAccessor
        );
    }
}
