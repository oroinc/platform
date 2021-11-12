<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Job\Context;

use Oro\Bundle\ImportExportBundle\Exception\RuntimeException;
use Oro\Bundle\ImportExportBundle\Job\Context\ContextAggregatorInterface;
use Oro\Bundle\ImportExportBundle\Job\Context\ContextAggregatorRegistry;

class ContextAggregatorRegistryTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldNotInitializeAggregatorsInConstructor()
    {
        $aggregator1 = $this->createMock(ContextAggregatorInterface::class);
        $aggregator1->expects(self::never())
            ->method('getType');

        new ContextAggregatorRegistry([$aggregator1]);
    }

    public function testGetAggregator()
    {
        $aggregator1 = $this->createMock(ContextAggregatorInterface::class);
        $aggregator1->expects(self::once())
            ->method('getType')
            ->willReturn('aggregator1');

        $contextAggregatorRegistry = new ContextAggregatorRegistry([$aggregator1]);
        self::assertSame($aggregator1, $contextAggregatorRegistry->getAggregator('aggregator1'));
        // test that aggregators are initialized only once
        self::assertSame($aggregator1, $contextAggregatorRegistry->getAggregator('aggregator1'));
    }

    public function testGetAggregatorForNotExistingAggregator()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The context aggregator "aggregator2" does not exist.');

        $aggregator1 = $this->createMock(ContextAggregatorInterface::class);
        $aggregator1->expects(self::once())
            ->method('getType')
            ->willReturn('aggregator1');

        $contextAggregatorRegistry = new ContextAggregatorRegistry([$aggregator1]);
        $contextAggregatorRegistry->getAggregator('aggregator2');
    }

    public function testReset()
    {
        $aggregator1 = $this->createMock(ContextAggregatorInterface::class);
        $aggregator1->expects(self::exactly(2))
            ->method('getType')
            ->willReturn('aggregator1');

        $contextAggregatorRegistry = new ContextAggregatorRegistry([$aggregator1]);
        self::assertSame($aggregator1, $contextAggregatorRegistry->getAggregator('aggregator1'));
        $contextAggregatorRegistry->reset();
        self::assertSame($aggregator1, $contextAggregatorRegistry->getAggregator('aggregator1'));
    }
}
