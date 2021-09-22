<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Event\EntityDataEvent;
use Oro\Bundle\EntityMergeBundle\MergeEvents;
use Oro\Bundle\EntityMergeBundle\Model\EntityMerger;
use Oro\Bundle\EntityMergeBundle\Model\Step\MergeStepInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EntityMergerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    }

    private function setMergeExpectations(EntityData $data): array
    {
        $step1 = $this->createMock(MergeStepInterface::class);
        $step1->expects($this->once())
            ->method('run')
            ->with($data);

        $step2 = $this->createMock(MergeStepInterface::class);
        $step2->expects($this->once())
            ->method('run')
            ->with($data);

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(EntityDataEvent::class), MergeEvents::BEFORE_MERGE_ENTITY],
                [$this->isInstanceOf(EntityDataEvent::class), MergeEvents::AFTER_MERGE_ENTITY]
            )
            ->willReturnCallback(function (EntityDataEvent $event) use ($data) {
                $this->assertSame($data, $event->getEntityData());

                return $event;
            });

        return [$step1, $step2];
    }

    public function testMergeWhenStepsAreArray(): void
    {
        $data = $this->createMock(EntityData::class);
        $steps = $this->setMergeExpectations($data);

        $merger = new EntityMerger($steps, $this->eventDispatcher);
        $merger->merge($data);
    }

    public function testMergeWhenStepsAreTraversable(): void
    {
        $data = $this->createMock(EntityData::class);
        $steps = $this->setMergeExpectations($data);

        $merger = new EntityMerger(new \ArrayIterator($steps), $this->eventDispatcher);
        $merger->merge($data);
    }
}
