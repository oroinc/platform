<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Job\Context;

use Oro\Bundle\ImportExportBundle\Job\Context\ContextAggregatorInterface;
use Oro\Bundle\ImportExportBundle\Job\Context\ContextAggregatorRegistry;

class ContextAggregatorRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAggregatorRegistry */
    private $contextAggregatorRegistry;

    protected function setUp()
    {
        $this->contextAggregatorRegistry = new ContextAggregatorRegistry();
    }

    public function testGetAggregator()
    {
        $aggregator1 = $this->createMock(ContextAggregatorInterface::class);
        $aggregator1->expects(self::once())
            ->method('getType')
            ->willReturn('aggregator1');

        $this->contextAggregatorRegistry->addAggregator($aggregator1);

        self::assertSame($aggregator1, $this->contextAggregatorRegistry->getAggregator('aggregator1'));
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\RuntimeException
     * @expectedExceptionMessage The context aggregator "aggregator2" does not exist.
     */
    public function testGetNonexistentAggregator()
    {
        $aggregator1 = $this->createMock(ContextAggregatorInterface::class);
        $aggregator1->expects(self::once())
            ->method('getType')
            ->willReturn('aggregator1');

        $this->contextAggregatorRegistry->addAggregator($aggregator1);

        $this->contextAggregatorRegistry->getAggregator('aggregator2');
    }
}
