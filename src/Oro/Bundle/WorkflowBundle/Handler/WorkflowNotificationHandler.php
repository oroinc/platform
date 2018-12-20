<?php

namespace Oro\Bundle\WorkflowBundle\Handler;

use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Event\Handler\EmailNotificationHandler;
use Oro\Bundle\NotificationBundle\Event\NotificationEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowNotificationEvent;

/**
 * Sends emails for workflow related notification events defined by notification rules.
 */
class WorkflowNotificationHandler extends EmailNotificationHandler
{
    /**
     * {@inheritdoc}
     */
    public function handle(NotificationEvent $event, array $matchedNotifications)
    {
        if (!$event instanceof WorkflowNotificationEvent) {
            return;
        }

        $transitionRecord = $event->getTransitionRecord();
        $workflowName = $transitionRecord->getWorkflowItem()->getWorkflowName();
        $transitionName = $transitionRecord->getTransitionName();

        // convert notification rules to a list of EmailNotificationInterface
        $notifications = [];
        foreach ($matchedNotifications as $notification) {
            if ($this->isApplicable($notification, $workflowName, $transitionName)) {
                $notifications[] = $this->getEmailNotificationAdapter($event, $notification);
            }
        }

        if ($notifications) {
            // send notifications
            $this->manager->process(
                $notifications,
                null,
                [
                    'transitionRecord' => $transitionRecord,
                    'transitionUser' => $event->getTransitionUser()
                ]
            );
        }

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
        $expectedWorkflowDefinition = $notification->getWorkflowDefinition();
        $expectedTransitionName = $notification->getWorkflowTransitionName();

        if (!$expectedWorkflowDefinition || !$expectedTransitionName) {
            return false;
        }

        return $workflowName === $expectedWorkflowDefinition->getName() && $transitionName === $expectedTransitionName;
    }
}
