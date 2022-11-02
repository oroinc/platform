<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Factory;

use Oro\Bundle\FilterBundle\Factory\FilterFactory;
use Oro\Bundle\FilterBundle\Filter\FilterBagInterface;
use Oro\Bundle\FilterBundle\Filter\FilterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;

class FilterFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var FilterBagInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $filterBag;

    /** @var FilterFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->filterBag = $this->createMock(FilterBagInterface::class);

        $this->factory = new FilterFactory($this->filterBag);
    }

    /**
     * @dataProvider invalidConfigDataProvider
     */
    public function testCreateFilterWhenInvalidConfig(array $filterConfig): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectErrorMessage(
            sprintf('The filter config was expected to contain "%s" key', FilterUtility::TYPE_KEY)
        );

        $this->factory->createFilter('sample_name', $filterConfig);
    }

    public function invalidConfigDataProvider(): array
    {
        return [
            'filter type missing' => [[]],
            'filter type is empty' => [[FilterUtility::TYPE_KEY => '']],
        ];
    }

    public function testCreateFilter(): void
    {
        $filterName = 'sample_name';
        $filterType = 'sample_type';
        $filterConfig = [FilterUtility::TYPE_KEY => $filterType];

        $filter = $this->createMock(FilterInterface::class);
        $this->filterBag->expects($this->once())
            ->method('getFilter')
            ->with($filterType)
            ->willReturn($filter);

        $filter->expects($this->once())
            ->method('init')
            ->with($filterName, $filterConfig);

        $createdFilter = $this->factory->createFilter($filterName, $filterConfig);
        $this->assertEquals($filter, $createdFilter);
        $this->assertNotSame($filter, $createdFilter);
    }
}
