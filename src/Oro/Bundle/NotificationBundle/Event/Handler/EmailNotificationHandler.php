<?php

namespace Oro\Bundle\NotificationBundle\Event\Handler;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\NotificationBundle\Event\NotificationEvent;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Processor\EmailNotificationProcessor;

class EmailNotificationHandler implements EventHandlerInterface
{
    /**
     * @var EmailNotificationProcessor
     */
    protected $processor;

    /**
     * @var EntityManager
     */
    protected $em;

    /** @var ConfigProvider */
    protected $configProvider;

    /**
     * Constructor
     *
     * @param EmailNotificationProcessor $processor
     * @param EntityManager              $em
     * @param ConfigProvider             $configProvider
     */
    public function __construct(
        EmailNotificationProcessor $processor,
        EntityManager $em,
        ConfigProvider $configProvider
    ) {
        $this->processor      = $processor;
        $this->em             = $em;
        $this->configProvider = $configProvider;
    }

    /**
     * Handle event
     *
     * @param NotificationEvent   $event
     * @param EmailNotification[] $matchedNotifications
     * @return mixed
     */
    public function handle(NotificationEvent $event, $matchedNotifications)
    {
        $entity = $event->getEntity();

        // convert notification rules to a list of EmailNotificationInterface
        $notifications = array();
        foreach ($matchedNotifications as $notification) {
            $notifications[] = new EmailNotificationAdapter(
                $entity,
                $notification,
                $this->em,
                $this->configProvider
            );
        }

        // send notifications
        $this->processor->process($entity, $notifications);
    }
}
