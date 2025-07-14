<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Job\Context;

use Oro\Bundle\ImportExportBundle\Exception\RuntimeException;
use Oro\Bundle\ImportExportBundle\Job\Context\ContextAggregatorInterface;
use Oro\Bundle\ImportExportBundle\Job\Context\ContextAggregatorRegistry;
use PHPUnit\Framework\TestCase;

class ContextAggregatorRegistryTest extends TestCase
{
    public function testShouldNotInitializeAggregatorsInConstructor(): void
    {
        $aggregator1 = $this->createMock(ContextAggregatorInterface::class);
        $aggregator1->expects(self::never())
            ->method('getType');

        new ContextAggregatorRegistry([$aggregator1]);
    }

    public function testGetAggregator(): void
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

    public function testGetAggregatorForNotExistingAggregator(): void
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

    public function testReset(): void
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
