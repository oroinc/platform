<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Events;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowItemSubscriber;

class WorkflowItemSubscriberTest extends \PHPUnit_Framework_TestCase
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
     * @var WorkflowItemSubscriber
     */
    protected $subscriber;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityConnector = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\EntityConnector')
            ->disableOriginalConstructor()
            ->getMock();

        $this->subscriber = new WorkflowItemSubscriber($this->doctrineHelper, $this->entityConnector);
    }

    public function testGetSubscribedEvents()
    {
        $events = $this->subscriber->getSubscribedEvents();
        $this->assertCount(2, $events);
        // @codingStandardsIgnoreStart
        $this->assertContains(Events::postPersist, $events);
        $this->assertContains(Events::preRemove, $events);
        // @codingStandardsIgnoreEnd
    }

    public function testPostPersist()
    {
        $entity = new \stdClass();
        $entityId = 1;

        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($entity));
        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->will($this->returnValue($entityId));
        $workflowItem->expects($this->once())
            ->method('setEntityId')
            ->with($entityId);

        $event = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($workflowItem));

        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $uow->expects($this->once())
            ->method('scheduleExtraUpdate')
            ->with($workflowItem, array('entityId' => array(null, $entityId)));

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));

        $event->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $this->subscriber->postPersist($event);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     * @expectedExceptionMessage Workflow item does not contain related entity
     */
    public function testPostPersistException()
    {
        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowItem->expects($this->once())
            ->method('getEntity');

        $event = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($workflowItem));

        $this->subscriber->postPersist($event);
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

        $this->subscriber->preRemove($event);
    }

    public function preRemoveDataProvider()
    {
        return array(
            'not aware entity' => array(),
            'aware entity without workflow item' => array(
                'isAware' => true,
            ),
            'aware entity with workflow item' => array(
                'isAware' => true,
                'hasWorkflowItem' => true,
            ),
        );
    }
}
