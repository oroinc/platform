<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Provider;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\FilterBundle\Factory\FilterFactory;
use Oro\Bundle\FilterBundle\Filter\FilterInterface;
use Oro\Bundle\FilterBundle\Provider\DatagridFiltersProvider;

class DatagridFiltersProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var FilterFactory|\PHPUnit\Framework\MockObject\MockObject */
    private FilterFactory $filterFactory;

    private DatagridFiltersProvider $provider;

    protected function setUp(): void
    {
        $this->filterFactory = $this->createMock(FilterFactory::class);
        $this->provider = new DatagridFiltersProvider($this->filterFactory, OrmDatasource::TYPE);
    }

    /**
     * @dataProvider getDatagridFiltersWhenNoFiltersDataProvider
     */
    public function testGetDatagridFiltersWhenNoFilters(array $gridConfigParams): void
    {
        $this->filterFactory
            ->expects($this->never())
            ->method($this->anything());

        $gridConfig = $this->getGridConfig(OrmDatasource::TYPE, $gridConfigParams);
        $this->assertEmpty($this->provider->getDatagridFilters($gridConfig));
    }

    public function getDatagridFiltersWhenNoFiltersDataProvider(): array
    {
        return [
            ['$gridConfig' => []],
            [
                '$gridConfig' => [
                    'filters' => [
                        'columns' => [
                            'sample_filter' => [PropertyInterface::DISABLED_KEY => true],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testGetDatagridFiltersWhenEmptyFilterConfig(): void
    {
        $filter1Name = 'sample_filter1';
        $gridConfig = $this->getGridConfig(OrmDatasource::TYPE, ['filters' => ['columns' => [$filter1Name => []]]]);
        $filter1 = $this->createMock(FilterInterface::class);

        $this->filterFactory
            ->expects($this->once())
            ->method('createFilter')
            ->with($filter1Name, ['label' => null])
            ->willReturn($filter1);

        $this->assertEquals([$filter1Name => $filter1], $this->provider->getDatagridFilters($gridConfig));
    }

    public function testGetDatagridFiltersWhenFilterWithLabel(): void
    {
        $filter1Name = 'sample_filter1';
        $gridConfig = $this->getGridConfig(
            OrmDatasource::TYPE,
            [
                'filters' => [
                    'columns' => [
                        $filter1Name => ['name' => $filter1Name],
                    ],
                ],
                'columns' => [$filter1Name => ['label' => $filter1Name]],
            ]
        );
        $filter1 = $this->createMock(FilterInterface::class);

        $this->filterFactory
            ->expects($this->once())
            ->method('createFilter')
            ->with($filter1Name, ['label' => $filter1Name, 'name' => $filter1Name])
            ->willReturn($filter1);

        $this->assertEquals([$filter1Name => $filter1], $this->provider->getDatagridFilters($gridConfig));
    }

    public function testGetDatagridFiltersWhenFilterDisabled(): void
    {
        $gridConfig = $this->getGridConfig(
            OrmDatasource::TYPE,
            [
                'filters' => [
                    'columns' => [
                        'sample_filter1' => [PropertyInterface::DISABLED_KEY => true],
                    ],
                ],
            ]
        );

        $this->filterFactory
            ->expects($this->never())
            ->method('createFilter');

        $this->assertEquals([], $this->provider->getDatagridFilters($gridConfig));
    }

    private function getGridConfig(string $datasourceType, array $gridConfig = []): DatagridConfiguration
    {
        $configuration = DatagridConfiguration::createNamed('sample_grid', $gridConfig);
        $configuration->setDatasourceType($datasourceType);

        return $configuration;
    }
}
