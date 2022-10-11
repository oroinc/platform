<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Async;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Async\Topic\WorkflowTransitionCronTriggerTopic;
use Oro\Bundle\WorkflowBundle\Async\Topic\WorkflowTransitionEventTriggerTopic;
use Oro\Bundle\WorkflowBundle\Async\TransitionTriggerMessage;
use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitionsWithTransitionTriggers;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

/**
 * @dbIsolationPerTest
 */
class TransitionTriggerProcessorTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadWorkflowDefinitionsWithTransitionTriggers::class]);

        // Enables optional listener that is disabled in functional tests.
        self::getContainer()
            ->get('oro_workflow.listener.event_trigger_collector')
            ->setEnabled(true);

        // Creates transition triggers for the loaded workflow definition.
        $workflowDefinition = $this->getReference('workflow.first_workflow_with_triggers');
        self::getContainer()
            ->get('oro_workflow.handler.workflow_definition')
            ->updateWorkflowDefinition($workflowDefinition, $workflowDefinition);
    }

    public function testProcessWhenNoTrigger(): void
    {
        $sentMessage = self::sendMessage(WorkflowTransitionEventTriggerTopic::getName(), [
            TransitionTriggerMessage::TRANSITION_TRIGGER => PHP_INT_MAX,
            TransitionTriggerMessage::MAIN_ENTITY => PHP_INT_MAX,
        ]);

        self::consume();

        self::assertProcessedMessageStatus(MessageProcessorInterface::REJECT, $sentMessage);
        self::assertProcessedMessageProcessor('oro_workflow.async.transition_trigger_event_processor', $sentMessage);

        self::assertTrue(
            self::getLoggerTestHandler()->hasErrorThatContains(
                'Transition trigger #' . PHP_INT_MAX . ' is not found'
            )
        );
    }

    public function testProcessEventTrigger(): void
    {
        $entity = new WorkflowAwareEntity();
        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(WorkflowAwareEntity::class);
        $entityManager->persist($entity);
        $entityManager->flush();

        $workflowManager = self::getContainer()->get('oro_workflow.manager');
        $workflowItem = $workflowManager->startWorkflow('first_workflow_with_triggers', $entity);

        $entity->setName('Sample name');
        $entityManager->persist($entity);
        $entityManager->flush();

        self::assertEquals('first_step', $workflowItem->getCurrentStep()->getName());
        $sentMessage = self::getSentMessage(WorkflowTransitionEventTriggerTopic::getName(), false);

        self::consume();

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor('oro_workflow.async.transition_trigger_event_processor', $sentMessage);

        self::assertEquals('second_step', $workflowItem->getCurrentStep()->getName());
    }

    public function testProcessEventTriggerWhenNotAllowed(): void
    {
        $entity = new WorkflowAwareEntity();
        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(WorkflowAwareEntity::class);
        $entityManager->persist($entity);
        $entityManager->flush();

        $workflowManager = self::getContainer()->get('oro_workflow.manager');
        $workflowItem = $workflowManager->startWorkflow('first_workflow_with_triggers', $entity);
        self::assertEquals('first_step', $workflowItem->getCurrentStep()->getName());

        $workflowManager->transit($workflowItem, 'second_transition');
        self::assertEquals('second_step', $workflowItem->getCurrentStep()->getName());

        $trigger = self::getContainer()
            ->get('doctrine')
            ->getRepository(TransitionEventTrigger::class)
            ->findOneBy(['workflowDefinition' => 'first_workflow_with_triggers']);

        $sentMessage = self::sendMessage(WorkflowTransitionEventTriggerTopic::getName(), [
            TransitionTriggerMessage::TRANSITION_TRIGGER => $trigger->getId(),
            TransitionTriggerMessage::MAIN_ENTITY => $entity->getId(),
        ]);

        self::consume();

        self::assertProcessedMessageStatus(MessageProcessorInterface::REJECT, $sentMessage);
        self::assertProcessedMessageProcessor('oro_workflow.async.transition_trigger_event_processor', $sentMessage);

        self::assertEquals('second_step', $workflowItem->getCurrentStep()->getName());
        self::assertTrue(self::getLoggerTestHandler()->hasWarningThatContains('Transition not allowed'));
    }

    public function testProcessCronTrigger(): void
    {
        $entity = new WorkflowAwareEntity();
        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(WorkflowAwareEntity::class);
        $entityManager->persist($entity);
        $entityManager->flush();

        $workflowManager = self::getContainer()->get('oro_workflow.manager');
        $workflowItem = $workflowManager->startWorkflow('first_workflow_with_triggers', $entity);

        self::assertEquals('first_step', $workflowItem->getCurrentStep()->getName());

        $trigger = self::getContainer()
            ->get('doctrine')
            ->getRepository(TransitionCronTrigger::class)
            ->findOneBy(['workflowDefinition' => 'first_workflow_with_triggers']);

        $sentMessage = self::sendMessage(WorkflowTransitionCronTriggerTopic::getName(), [
            TransitionTriggerMessage::TRANSITION_TRIGGER => $trigger->getId(),
            TransitionTriggerMessage::MAIN_ENTITY => $entity->getId(),
        ]);

        self::consume();

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor('oro_workflow.async.transition_trigger_cron_processor', $sentMessage);

        self::assertEquals('second_step', $workflowItem->getCurrentStep()->getName());
    }
}
