<?php

namespace Oro\Bundle\NotificationBundle\Event\Handler;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\NotificationBundle\Event\NotificationEvent;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;

class EmailNotificationHandler implements EventHandlerInterface
{
    /**
     * @var EmailNotificationManager
     */
    protected $manager;

    /**
     * @var EntityManager
     */
    protected $em;

    /** @var ConfigProvider */
    protected $configProvider;

    /**
     * Constructor
     *
     * @param EmailNotificationManager $manager
     * @param EntityManager              $em
     * @param ConfigProvider             $configProvider
     */
    public function __construct(
        EmailNotificationManager $manager,
        EntityManager $em,
        ConfigProvider $configProvider
    ) {
        $this->manager      = $manager;
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
        $this->manager->process($entity, $notifications);
    }
}
