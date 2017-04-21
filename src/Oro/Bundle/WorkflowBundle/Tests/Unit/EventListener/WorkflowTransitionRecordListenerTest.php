<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;
use Oro\Bundle\WorkflowBundle\Event\WorkflowNotificationEvent;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowTransitionRecordListener;
use Oro\Bundle\WorkflowBundle\Migrations\Data\ORM\LoadWorkflowNotificationEvents;

class WorkflowTransitionRecordListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject */
    private $args;

    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $eventDispatcher;

    /** @var WorkflowTransitionRecordListener */
    private $listener;

    protected function setUp()
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->args = $this->createMock(LifecycleEventArgs::class);

        $this->listener = new WorkflowTransitionRecordListener($this->eventDispatcher);
    }

    /**
     * @dataProvider setEnabledAndPostPersistDataProvider
     *
     * @param bool $enabled
     * @param WorkflowTransitionRecord|\PHPUnit_Framework_MockObject_MockObject $transitionRecord
     * @param bool $expected
     */
    public function testSetEnabledAndPostPersist($enabled, $transitionRecord, $expected)
    {
        $entity = new \stdClass();

        $this->listener->setEnabled($enabled);

        $this->args->expects($this->once())
            ->method('getEntity')
            ->willReturn($transitionRecord);

        $expectedCount = (int) $expected;

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->exactly($expectedCount))
            ->method('getEntity')
            ->willReturn($entity);

        if ($expected) {
            $transitionRecord->expects($this->once())
                ->method('getWorkflowItem')
                ->willReturn($workflowItem);
        }

        $event = $expected ? new WorkflowNotificationEvent($entity, $transitionRecord) : null;

        $this->eventDispatcher->expects($this->exactly($expectedCount))
            ->method('dispatch')
            ->with(LoadWorkflowNotificationEvents::TRANSIT_EVENT, $event);

        $this->listener->postPersist($this->args);
    }

    /**
     * @return array
     */
    public function setEnabledAndPostPersistDataProvider()
    {
        $transitionRecord = $this->createMock(WorkflowTransitionRecord::class);

        return [
            'not enabled' => [
                'enabled' => false,
                'transitionRecord' => $transitionRecord,
                'expected' => false
            ],
            'not transition record' => [
                'enabled' => true,
                'transitionRecord' => $this->createMock(\stdClass::class),
                'expected' => false
            ],
            'enabled and transition record' => [
                'enabled' => true,
                'transitionRecord' => $transitionRecord,
                'expected' => true
            ],
        ];
    }
}
