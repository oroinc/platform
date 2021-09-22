<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Step;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Event\FieldDataEvent;
use Oro\Bundle\EntityMergeBundle\MergeEvents;
use Oro\Bundle\EntityMergeBundle\Model\Step\MergeFieldsStep;
use Oro\Bundle\EntityMergeBundle\Model\Strategy\StrategyInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MergerFieldsStepTest extends \PHPUnit\Framework\TestCase
{
    /** @var StrategyInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $strategy;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var MergeFieldsStep */
    private $step;

    protected function setUp(): void
    {
        $this->strategy = $this->createMock(StrategyInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->step = new MergeFieldsStep($this->strategy, $this->eventDispatcher);
    }

    public function testRun(): void
    {
        $foo = $this->createMock(FieldData::class);
        $bar = $this->createMock(FieldData::class);

        $data = $this->createMock(EntityData::class);
        $data->expects($this->once())
            ->method('getFields')
            ->willReturn([$foo, $bar]);

        $this->strategy->expects($this->exactly(2))
            ->method('merge')
            ->withConsecutive(
                [$this->identicalTo($foo)],
                [$this->identicalTo($bar)]
            );

        $calls = [];
        $this->eventDispatcher->expects($this->exactly(4))
            ->method('dispatch')
            ->willReturnCallback(function (FieldDataEvent $event, string $eventName) use (&$calls, $foo, $bar) {
                $field = 'UNKNOWN';
                if ($event->getFieldData() === $foo) {
                    $field = 'foo';
                } elseif ($event->getFieldData() === $bar) {
                    $field = 'bar';
                }
                $calls[] = $eventName . ' - ' . $field;

                return $event;
            });

        $this->step->run($data);

        $this->assertEquals(
            [
                MergeEvents::BEFORE_MERGE_FIELD . ' - foo',
                MergeEvents::AFTER_MERGE_FIELD . ' - foo',
                MergeEvents::BEFORE_MERGE_FIELD . ' - bar',
                MergeEvents::AFTER_MERGE_FIELD . ' - bar'
            ],
            $calls
        );
    }
}
