<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowItemListener;

class WorkflowItemListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityConnector;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $workflowManager;

    /**
     * @var WorkflowItemListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityConnector = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\EntityConnector')
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflowManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new WorkflowItemListener(
            $this->doctrineHelper,
            $this->entityConnector,
            $this->workflowManager
        );
    }

    public function testUpdateWorkflowItemEntityRelation()
    {
        $entity = new \stdClass();
        $entityId = 1;
        $entityClass = 'stdClass';

        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($entity));
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn($entityId);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($entity)
            ->willReturn($entityClass);
        $workflowItem->expects($this->once())
            ->method('setEntityId')
            ->with($entityId);
        $workflowItem->expects($this->once())
            ->method('setEntityClass')
            ->with($entityClass);

        $event = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->atLeastOnce())
            ->method('getEntity')
            ->will($this->returnValue($workflowItem));

        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $uow->expects($this->once())
            ->method('scheduleExtraUpdate')
            ->with($workflowItem, ['entityId' => [null, $entityId], 'entityClass' => [null, $entityClass]]);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));

        $event->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $this->listener->postPersist($event);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     * @expectedExceptionMessage Workflow item does not contain related entity
     */
    public function testUpdateWorkflowItemEntityRelationException()
    {
        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowItem->expects($this->once())
            ->method('getEntity');

        $event = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->atLeastOnce())
            ->method('getEntity')
            ->will($this->returnValue($workflowItem));

        $this->listener->postPersist($event);
    }

    /**
     * @param bool $isAware
     * @param bool $hasWorkflowItem
     * @dataProvider preRemoveDataProvider
     */
    public function testPreRemove($isAware = false, $hasWorkflowItem = false)
    {
        $entity = new \DateTime();
        $workflowItem = new WorkflowItem();

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityConnector->expects($this->once())
            ->method('isWorkflowAware')
            ->with($entity)
            ->will($this->returnValue($isAware));
        if ($isAware) {
            $this->entityConnector->expects($this->once())
                ->method('getWorkflowItem')
                ->with($entity)
                ->will($this->returnValue($hasWorkflowItem ? $workflowItem : null));
            if ($hasWorkflowItem) {
                $entityManager->expects($this->once())
                    ->method('remove')
                    ->with($workflowItem);
            } else {
                $entityManager->expects($this->never())
                    ->method('remove');
            }
        } else {
            $this->entityConnector->expects($this->never())
                ->method('getWorkflowItem');
        }

        $event = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())
            ->method('getEntity')
            ->will($this->returnValue($entity));
        $event->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($entityManager));

        $this->listener->preRemove($event);
    }

    public function preRemoveDataProvider()
    {
        return [
            'not aware entity' => [],
            'aware entity without workflow item' => [
                'isAware' => true,
            ],
            'aware entity with workflow item' => [
                'isAware' => true,
                'hasWorkflowItem' => true,
            ],
        ];
    }

    public function testScheduleStartWorkflowForNewEntityNoWorkflow()
    {
        $entity = new \stdClass();

        $event = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())
            ->method('getEntity')
            ->will($this->returnValue($entity));
        $this->workflowManager->expects($this->atLeastOnce())
            ->method('getApplicableWorkflows')
            ->with($entity);

        $this->listener->postPersist($event);
        $this->assertAttributeEmpty('entitiesScheduledForWorkflowStart', $this->listener);
    }

    public function testScheduleStartWorkflowForNewEntityNoStartStep()
    {
        $entity = new \stdClass();

        $event = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())
            ->method('getEntity')
            ->will($this->returnValue($entity));

        $stepManager = $this->getMock('Oro\Bundle\WorkflowBundle\Model\StepManager');
        $stepManager->expects($this->any())->method('hasStartStep')
            ->will($this->returnValue(false));

        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->getMock();
        $workflow->expects($this->any())
            ->method('getStepManager')
            ->will($this->returnValue($stepManager));

        $this->workflowManager->expects($this->atLeastOnce())
            ->method('getApplicableWorkflows')
            ->with($entity)
            ->will($this->returnValue([$workflow]));

        $this->listener->postPersist($event);
        $this->assertAttributeEmpty('entitiesScheduledForWorkflowStart', $this->listener);
    }

    public function testStartWorkflowForNewEntity()
    {
        $entity = new \stdClass();
        $childEntity = new \DateTime();

        list($event, $workflow) = $this->prepareEventForWorkflow($entity);
        $this->workflowManager->expects($this->at(0))
            ->method('getApplicableWorkflows')
            ->with($entity)
            ->will($this->returnValue([$workflow]));

        list($childEvent, $childWorkflow) = $this->prepareEventForWorkflow($childEntity);
        $this->workflowManager->expects($this->at(2))
            ->method('getApplicableWorkflows')
            ->with($childEntity)
            ->will($this->returnValue([$childWorkflow]));

        $this->listener->postPersist($event);

        $expectedSchedule = [
            0 => [
                [
                    'entity' => $entity,
                    'workflow' => $workflow
                ],
            ],
        ];
        $this->assertAttributeEquals(0, 'deepLevel', $this->listener);
        $this->assertAttributeEquals($expectedSchedule, 'entitiesScheduledForWorkflowStart', $this->listener);

        $startChildWorkflow = function () use ($childEvent, $childEntity, $childWorkflow) {
            $this->listener->postPersist($childEvent);

            $expectedSchedule = [
                1 => [
                    [
                        'entity' => $childEntity,
                        'workflow' => $childWorkflow
                    ],
                ],
            ];
            $this->assertAttributeEquals(1, 'deepLevel', $this->listener);
            $this->assertAttributeEquals($expectedSchedule, 'entitiesScheduledForWorkflowStart', $this->listener);

            $this->listener->postFlush();

            $this->assertAttributeEquals(1, 'deepLevel', $this->listener);
            $this->assertAttributeEmpty('entitiesScheduledForWorkflowStart', $this->listener);
        };

        $this->workflowManager->expects($this->at(0))
            ->method('massStartWorkflow')
            ->with([['workflow' => $workflow, 'entity' => $entity]])
            ->will($this->returnCallback($startChildWorkflow));
        $this->workflowManager->expects($this->at(1))
            ->method('massStartWorkflow')
            ->with([['workflow' => $childWorkflow, 'entity' => $childEntity]]);

        $this->listener->postFlush();

        $this->assertAttributeEquals(0, 'deepLevel', $this->listener);
        $this->assertAttributeEmpty('entitiesScheduledForWorkflowStart', $this->listener);
    }

    /**
     * @param object $entity
     * @return array
     */
    protected function prepareEventForWorkflow($entity)
    {
        $event = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())
            ->method('getEntity')
            ->will($this->returnValue($entity));

        $stepManager = $this->getMock('Oro\Bundle\WorkflowBundle\Model\StepManager');
        $stepManager->expects($this->any())->method('hasStartStep')
            ->will($this->returnValue(true));

        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->getMock();
        $workflow->expects($this->any())
            ->method('getStepManager')
            ->will($this->returnValue($stepManager));

        return [$event, $workflow];
    }
}
