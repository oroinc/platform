<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Filter;

use Oro\Bundle\FilterBundle\Filter\FilterBag;
use Oro\Bundle\FilterBundle\Filter\FilterInterface;
use Psr\Container\ContainerInterface;

class FilterBagTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $filterContainer;

    /** @var FilterBag */
    private $filterBag;

    protected function setUp(): void
    {
        $this->filterContainer = $this->createMock(ContainerInterface::class);

        $this->filterBag = new FilterBag(
            ['filter1', 'filter2'],
            $this->filterContainer
        );
    }

    public function testGetFilterNames()
    {
        self::assertEquals(['filter1', 'filter2'], $this->filterBag->getFilterNames());
    }

    public function testHasFilter()
    {
        $filterName = 'filter1';

        $this->filterContainer->expects(self::once())
            ->method('has')
            ->with($filterName)
            ->willReturn(true);

        self::assertTrue($this->filterBag->hasFilter($filterName));
    }

    public function testGetFilter()
    {
        $filterName = 'filter1';
        $filter = $this->createMock(FilterInterface::class);
        $filter->expects(self::once())
            ->method('reset');

        $this->filterContainer->expects(self::once())
            ->method('get')
            ->with($filterName)
            ->willReturn($filter);

        self::assertSame($filter, $this->filterBag->getFilter($filterName));
    }
}
