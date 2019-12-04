<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;
use Oro\Bundle\WorkflowBundle\Event\WorkflowNotificationEvent;
use Oro\Bundle\WorkflowBundle\Tests\Functional\Environment\EmailNotificationHandler;
use Oro\Bundle\WorkflowBundle\Tests\Functional\Environment\NotificationManager;

class WorkflowNotificationHandlerTest extends WebTestCase
{
    /** @var NotificationManager */
    private $notificationManager;

    protected function setUp()
    {
        $this->initClient();

        $this->notificationManager = $this->getContainer()->get('oro_workflow.notification_manager');
    }

    public function testEmailNotificationHandlerIsNotCalled(): void
    {
        /** @var EmailNotificationHandler $emailNotificationHandler */
        $emailNotificationHandler = $this->getContainer()->get('oro_workflow.notification_email_handler');
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
        $handlerIds = $this->notificationManager->getHandlerIds();
        $this->assertEquals('oro_workflow.handler.workflow_notification_handler', $handlerIds[0]);
    }
}
