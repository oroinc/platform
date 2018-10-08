<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\DataAuditBundle\Model\AdditionalEntityChangesToAuditStorage;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;
use Oro\Bundle\WorkflowBundle\EventListener\SendWorkflowStepChangesToAuditListener;

class SendWorkflowStepChangesToAuditListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AdditionalEntityChangesToAuditStorage
     */
    private $storage;

    /**
     * @var SendWorkflowStepChangesToAuditListener
     */
    private $listener;

    protected function setUp()
    {
        $this->storage = new AdditionalEntityChangesToAuditStorage();
        $this->listener = new SendWorkflowStepChangesToAuditListener($this->storage);
    }

    public function testPostPersistWhenDisabled()
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $workflowTransitionRecord = new WorkflowTransitionRecord();
        $event = new LifecycleEventArgs($workflowTransitionRecord, $entityManager);
        $this->listener->setEnabled(false);
        $this->listener->postPersist($workflowTransitionRecord, $event);
        $this->listener->setEnabled(true);

        $expectedUpdates = new \SplObjectStorage();
        $this->assertEquals($expectedUpdates, $this->storage->getEntityUpdates($entityManager));
    }

    public function testPostPersistWhenNoEntityInWorkflowItem()
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $workflowTransitionRecord = new WorkflowTransitionRecord();
        $workflowItem = new WorkflowItem();
        $workflowItem->addTransitionRecord($workflowTransitionRecord);
        $event = new LifecycleEventArgs($workflowTransitionRecord, $entityManager);
        $this->listener->postPersist($workflowTransitionRecord, $event);

        $this->assertEquals(new \SplObjectStorage(), $this->storage->getEntityUpdates($entityManager));
    }

    public function testPostPersist()
    {
        $entity = new \stdClass();
        $workflowStepFrom = new WorkflowStep();
        $workflowStepFrom->setName('from');
        $workflowStepTo = new WorkflowStep();
        $workflowStepTo->setName('to');
        $workflowTransitionRecord = new WorkflowTransitionRecord();
        $workflowTransitionRecord->setStepFrom($workflowStepFrom);
        $workflowTransitionRecord->setStepTo($workflowStepTo);
        $workflowItem = new WorkflowItem();
        $workflowItem->setEntity($entity);
        $workflowItem->addTransitionRecord($workflowTransitionRecord);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $event = new LifecycleEventArgs($workflowTransitionRecord, $entityManager);
        $this->listener->postPersist($workflowTransitionRecord, $event);

        $expectedUpdates = new \SplObjectStorage();
        $expectedUpdates->attach(
            $entity,
            [
                SendWorkflowStepChangesToAuditListener::FIELD_ALIAS => [
                    $workflowStepFrom,
                    $workflowStepTo
                ]
            ]
        );
        $this->assertEquals($expectedUpdates, $this->storage->getEntityUpdates($entityManager));
    }
}
