<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowItemListener;
use Oro\Bundle\WorkflowBundle\Model\WorkflowEntityConnector;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowStartArguments;

class WorkflowItemListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowManager;

    /** @var WorkflowEntityConnector|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityConnector;

    /** @var WorkflowItemListener */
    protected $listener;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflowManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityConnector = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowEntityConnector')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new WorkflowItemListener(
            $this->doctrineHelper,
            $this->workflowManager,
            $this->entityConnector
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

        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $uow->expects($this->once())
            ->method('scheduleExtraUpdate')
            ->with($workflowItem, ['entityId' => [null, $entityId], 'entityClass' => [null, $entityClass]]);

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));

        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflowManager->expects($this->once())
            ->method('getApplicableWorkflows')
            ->with($workflowItem)
            ->willReturn([$workflow]);

        $stepManager = $this->getMock('Oro\Bundle\WorkflowBundle\Model\StepManager');
        $stepManager->expects($this->any())->method('hasStartStep')
            ->will($this->returnValue(false));

        $workflow->expects($this->any())
            ->method('getStepManager')
            ->will($this->returnValue($stepManager));

        $this->listener->postPersist($this->getEvent($workflowItem, $em));
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

        $this->listener->postPersist($this->getEvent($workflowItem));
    }

    /**
     * @param bool $hasWorkflowItems
     * @dataProvider preRemoveDataProvider
     */
    public function testPreRemove($hasWorkflowItems = false)
    {
        $entity = new \DateTime();
        $workflowItem = new WorkflowItem();



        $this->entityConnector->expects($this->once())
            ->method('isApplicableEntity')
            ->with($entity)
            ->willReturn(true);

        $this->workflowManager->expects($this->once())
            ->method('getWorkflowItemsByEntity')
            ->with($entity)
            ->willReturn($hasWorkflowItems ? [$workflowItem] : null);
        $entityManager = null;
        if ($hasWorkflowItems) {
            $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
                ->disableOriginalConstructor()
                ->getMock();
            $entityManager->expects($this->once())
                ->method('remove')
                ->with($workflowItem);
        }

        $this->listener->preRemove($this->getEvent($entity, $entityManager));
    }

    /**
     * @return array
     */
    public function preRemoveDataProvider()
    {
        return array(
            'aware entity without workflow item' => array(),
            'aware entity with workflow item' => array(
                'hasWorkflowItem' => true,
            ),
        );
    }

    public function testPreRemoveWithUnsupportedEntity()
    {
        $entity = new \DateTime();

        $this->entityConnector->expects($this->once())
            ->method('isApplicableEntity')
            ->with($entity)
            ->willReturn(false);

        $this->workflowManager->expects($this->never())->method($this->anything());

        $this->listener->preRemove($this->getEvent($entity));
    }

    public function testScheduleStartWorkflowForNewEntityNoWorkflow()
    {
        $entity = new \stdClass();

        $this->workflowManager->expects($this->once())
            ->method('getApplicableWorkflows')
            ->with($entity)
            ->willReturn([]);

        $this->listener->postPersist($this->getEvent($entity));

        $this->assertAttributeEmpty('entitiesScheduledForWorkflowStart', $this->listener);
    }

    public function testScheduleStartWorkflowForNewEntityNoStartStep()
    {
        $entity = new \stdClass();

        $stepManager = $this->getMock('Oro\Bundle\WorkflowBundle\Model\StepManager');
        $stepManager->expects($this->any())->method('hasStartStep')
            ->will($this->returnValue(false));

        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->getMock();
        $workflow->expects($this->any())
            ->method('getStepManager')
            ->will($this->returnValue($stepManager));

        $this->workflowManager->expects($this->once())
            ->method('getApplicableWorkflows')
            ->with($entity)
            ->willReturn([$workflow]);

        $this->listener->postPersist($this->getEvent($entity));
        $this->assertAttributeEmpty('entitiesScheduledForWorkflowStart', $this->listener);
    }

    public function testStartWorkflowForNewEntity()
    {
        $entity = new \stdClass();
        $childEntity = new \DateTime();
        $workflowName = 'test_workflow';
        $childWorkflowName = 'test_child_workflow';

        list($event, $workflow) = $this->prepareEventForWorkflow($entity, $workflowName);
        $this->workflowManager->expects($this->at(0))
            ->method('getApplicableWorkflows')
            ->with($entity)
            ->willReturn([$workflow]);

        list($childEvent, $childWorkflow) = $this->prepareEventForWorkflow($childEntity, $childWorkflowName);
        $this->workflowManager->expects($this->at(2))
            ->method('getApplicableWorkflows')
            ->with($childEntity)
            ->willReturn([$childWorkflow]);

        $this->listener->postPersist($event);

        $expectedSchedule = array(
            0 => array(
                new WorkflowStartArguments($workflowName, $entity),
            ),
        );
        $this->assertAttributeEquals(0, 'deepLevel', $this->listener);
        $this->assertAttributeEquals($expectedSchedule, 'entitiesScheduledForWorkflowStart', $this->listener);

        $startChildWorkflow = function () use ($childEvent, $childEntity, $childWorkflow, $childWorkflowName) {
            $this->listener->postPersist($childEvent);

            $expectedSchedule = array(
                1 => array(
                    new WorkflowStartArguments($childWorkflowName, $childEntity)
                ),
            );
            $this->assertAttributeEquals(1, 'deepLevel', $this->listener);
            $this->assertAttributeEquals($expectedSchedule, 'entitiesScheduledForWorkflowStart', $this->listener);

            $this->listener->postFlush();

            $this->assertAttributeEquals(1, 'deepLevel', $this->listener);
            $this->assertAttributeEmpty('entitiesScheduledForWorkflowStart', $this->listener);
        };

        $this->workflowManager->expects($this->at(0))
            ->method('massStartWorkflow')
            ->with([new WorkflowStartArguments($workflowName, $entity)])
            ->will($this->returnCallback($startChildWorkflow));
        $this->workflowManager->expects($this->at(1))
            ->method('massStartWorkflow')
            ->with([new WorkflowStartArguments($childWorkflowName, $childEntity)]);

        $this->listener->postFlush();

        $this->assertAttributeEquals(0, 'deepLevel', $this->listener);
        $this->assertAttributeEmpty('entitiesScheduledForWorkflowStart', $this->listener);
    }

    /**
     * @param object $entity
     * @param string $workflowName
     * @return array
     */
    protected function prepareEventForWorkflow($entity, $workflowName)
    {
        $event = $this->getEvent($entity);

        $stepManager = $this->getMock('Oro\Bundle\WorkflowBundle\Model\StepManager');
        $stepManager->expects($this->any())->method('hasStartStep')
            ->will($this->returnValue(true));

        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->getMock();
        $workflow->expects($this->any())
            ->method('getStepManager')
            ->will($this->returnValue($stepManager));
        $workflow->expects($this->any())->method('getName')->willReturn($workflowName);

        return array($event, $workflow);
    }

    /**
     * @param $entity
     * @param EntityManager|null $entityManager
     * @return LifecycleEventArgs
     */
    private function getEvent($entity, EntityManager $entityManager = null)
    {
        $event = $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->atLeastOnce())
            ->method('getEntity')
            ->will($this->returnValue($entity));
        $event->expects($this->exactly($entityManager ? 1 : 0))
            ->method('getEntityManager')
            ->will($this->returnValue($entityManager));

        return $event;
    }
}
