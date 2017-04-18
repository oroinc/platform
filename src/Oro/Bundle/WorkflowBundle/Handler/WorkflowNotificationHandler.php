<?php

namespace Oro\Bundle\WorkflowBundle\Handler;

use Oro\Bundle\NotificationBundle\Event\Handler\EmailNotificationHandler;
use Oro\Bundle\NotificationBundle\Event\NotificationEvent;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\WorkflowBundle\Event\WorkflowNotificationEvent;

class WorkflowNotificationHandler extends EmailNotificationHandler
{
    /**
     * {@inheritdoc}
     */
    public function handle(NotificationEvent $event, $matchedNotifications)
    {
        if (!$event instanceof WorkflowNotificationEvent) {
            return;
        }

        $workflowName = $event->getTransitionRecord()->getWorkflowItem()->getWorkflowName();
        $transitionName = $event->getTransitionRecord()->getTransitionName();

        // convert notification rules to a list of EmailNotificationInterface
        $notifications = [];
        foreach ($matchedNotifications as $notification) {
            if ($this->isApplicable($notification, $workflowName, $transitionName)) {
                $notifications[] = $this->getEmailNotificationAdapter($event, $notification);
            }
        }

        // send notifications
        $this->manager->process($event->getEntity(), $notifications);

        $event->stopPropagation();
    }

    /**
     * @param EmailNotification $notification
     * @param string $workflowName
     * @param string $transitionName
     *
     * @return bool
     */
    private function isApplicable(EmailNotification $notification, $workflowName, $transitionName)
    {
        $expectedWorkflowName = $notification->getWorkflowDefinition()->getName();
        $expectedTransitionName = $notification->getWorkflowTransitionName();

        return $workflowName === $expectedWorkflowName && $transitionName === $expectedTransitionName;
    }
}
