<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;
use Oro\Bundle\WorkflowBundle\Event\WorkflowNotificationEvent;
use Oro\Bundle\WorkflowBundle\Handler\WorkflowNotificationHandler;
use Oro\Bundle\WorkflowBundle\Tests\Functional\Environment\EmailNotificationHandler;
use Oro\Bundle\WorkflowBundle\Tests\Functional\Environment\NotificationManager;

class WorkflowNotificationHandlerTest extends WebTestCase
{
    /** @var NotificationManager */
    private $notificationManager;

    protected function setUp(): void
    {
        $this->initClient();

        $this->notificationManager = $this->getContainer()->get('oro_notification.manager');

        $this->getOptionalListenerManager()->enableListener(
            'oro_dataaudit.listener.send_changed_entities_to_message_queue'
        );
        $this->getOptionalListenerManager()->enableListener(
            'oro_workflow.event_listener.send_workflow_step_changes_to_audit'
        );
        $this->getOptionalListenerManager()->enableListener(
            'oro_workflow.listener.event_trigger_collector'
        );
        $this->getOptionalListenerManager()->enableListener(
            'oro_workflow.listener.workflow_transition_record'
        );
    }

    public function testEmailNotificationHandlerIsNotCalled(): void
    {
        /** @var EmailNotificationHandler $emailNotificationHandler */
        $emailNotificationHandler = $this->getContainer()->get('oro_notification.email_handler');
        $handleCount = $emailNotificationHandler->getHandleCount();

        $workflowTransitionRecord = new WorkflowTransitionRecord();

        $workflowItem = new WorkflowItem();
        $workflowItem->setWorkflowName('sample_workflow');

        $workflowTransitionRecord->setWorkflowItem($workflowItem);
        $workflowTransitionRecord->setTransitionName('sample_transition');

        $this->notificationManager->process(
            new WorkflowNotificationEvent(new \stdClass(), $workflowTransitionRecord),
            'oro.workflow.event.notification.workflow_transition'
        );

        $this->assertEquals($handleCount, $emailNotificationHandler->getHandleCount());

        $emailNotificationHandler->clearHandleCount();
    }

    public function testWorkflowNotificationHandlerIsInTheTop(): void
    {
        $handlers = $this->notificationManager->getHandlers();
        $this->assertInstanceOf(WorkflowNotificationHandler::class, $handlers[0]);
    }
}
