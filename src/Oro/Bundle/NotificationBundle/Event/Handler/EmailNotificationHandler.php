<?php

namespace Oro\Bundle\NotificationBundle\Event\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Event\NotificationEvent;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotificationInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Email handler sends emails for notification events defined by notification rules.
 */
class EmailNotificationHandler implements EventHandlerInterface
{
    /** @var EmailNotificationManager */
    protected $manager;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param EmailNotificationManager $manager
     * @param ManagerRegistry          $doctrine
     * @param PropertyAccessor         $propertyAccessor
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        EmailNotificationManager $manager,
        ManagerRegistry $doctrine,
        PropertyAccessor $propertyAccessor,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->manager = $manager;
        $this->doctrine = $doctrine;
        $this->propertyAccessor = $propertyAccessor;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(NotificationEvent $event, array $matchedNotifications)
    {
        // convert notification rules to a list of EmailNotificationInterface
        $notifications = [];
        foreach ($matchedNotifications as $notification) {
            $notifications[] = $this->getEmailNotificationAdapter($event, $notification);
        }

        // send notifications
        $this->manager->process($notifications);
    }

    /**
     * @param NotificationEvent $event
     * @param EmailNotification $notification
     *
     * @return TemplateEmailNotificationInterface
     */
    protected function getEmailNotificationAdapter(
        NotificationEvent $event,
        EmailNotification $notification
    ): TemplateEmailNotificationInterface {
        return new TemplateEmailNotificationAdapter(
            $event->getEntity(),
            $notification,
            $this->doctrine->getManager(),
            $this->propertyAccessor,
            $this->eventDispatcher
        );
    }
}
