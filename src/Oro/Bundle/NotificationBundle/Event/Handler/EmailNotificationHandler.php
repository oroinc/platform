<?php

namespace Oro\Bundle\NotificationBundle\Event\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Event\NotificationEvent;
use Oro\Bundle\NotificationBundle\Helper\WebsiteAwareEntityHelper;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotificationInterface;
use Oro\Bundle\NotificationBundle\Provider\ChainAdditionalEmailAssociationProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Email handler sends emails for notification events defined by notification rules.
 */
class EmailNotificationHandler implements EventHandlerInterface
{
    /** @var EmailNotificationManager */
    protected $manager;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var PropertyAccessorInterface */
    protected $propertyAccessor;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var ChainAdditionalEmailAssociationProvider */
    private $additionalEmailAssociationProvider;

    private WebsiteAwareEntityHelper $websiteAwareEntityHelper;

    public function __construct(
        EmailNotificationManager $manager,
        ManagerRegistry $doctrine,
        PropertyAccessorInterface $propertyAccessor,
        EventDispatcherInterface $eventDispatcher,
        ChainAdditionalEmailAssociationProvider $additionalEmailAssociationProvider,
        WebsiteAwareEntityHelper $websiteAwareEntityHelper,
    ) {
        $this->manager = $manager;
        $this->doctrine = $doctrine;
        $this->propertyAccessor = $propertyAccessor;
        $this->eventDispatcher = $eventDispatcher;
        $this->additionalEmailAssociationProvider = $additionalEmailAssociationProvider;
        $this->websiteAwareEntityHelper = $websiteAwareEntityHelper;
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

    protected function getEmailNotificationAdapter(
        NotificationEvent $event,
        EmailNotification $notification
    ): TemplateEmailNotificationInterface {
        return new TemplateEmailNotificationAdapter(
            $event->getEntity(),
            $notification,
            $this->doctrine->getManager(),
            $this->propertyAccessor,
            $this->eventDispatcher,
            $this->additionalEmailAssociationProvider,
            $this->websiteAwareEntityHelper
        );
    }
}
