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
    private $filterFactory;

    /** @var DatagridFiltersProvider */
    private $provider;

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
        $this->filterFactory->expects($this->never())
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

        $this->filterFactory->expects($this->once())
            ->method('createFilter')
            ->with($filter1Name, ['label' => null, 'order' => 1, 'disabled' => null])
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
                        $filter1Name => ['name' => $filter1Name]
                    ]
                ],
                'columns' => [
                    $filter1Name => ['label' => $filter1Name]
                ]
            ]
        );
        $filter1 = $this->createMock(FilterInterface::class);

        $this->filterFactory->expects($this->once())
            ->method('createFilter')
            ->with(
                $filter1Name,
                [
                    'name' => $filter1Name,
                    'label' => $filter1Name,
                    'order' => 1,
                    'disabled' => null
                ]
            )
            ->willReturn($filter1);

        $this->assertEquals([$filter1Name => $filter1], $this->provider->getDatagridFilters($gridConfig));
    }

    public function testGetDatagridFiltersWhenFilterWithNonTranslatableLabel(): void
    {
        $filter1Name = 'sample_filter1';
        $gridConfig = $this->getGridConfig(
            OrmDatasource::TYPE,
            [
                'filters' => [
                    'columns' => [
                        $filter1Name => ['name' => $filter1Name]
                    ]
                ],
                'columns' => [
                    $filter1Name => ['label' => $filter1Name, 'translatable' => false]
                ]
            ]
        );
        $filter1 = $this->createMock(FilterInterface::class);

        $this->filterFactory->expects($this->once())
            ->method('createFilter')
            ->with(
                $filter1Name,
                [
                    'name' => $filter1Name,
                    'label' => $filter1Name,
                    'translatable' => false,
                    'order' => 1,
                    'disabled' => null
                ]
            )
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

        $this->filterFactory->expects($this->never())
            ->method('createFilter');

        $this->assertEquals([], $this->provider->getDatagridFilters($gridConfig));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetDatagridFiltersCombinedProvider(): array
    {
        $filter1Name = 'sample_filter1';
        $filter2Name = 'sample_filter2';

        return [
            'follow column order' => [
                'configData'      => [
                    'filters' => [
                        'columns' => [
                            $filter1Name => [
                                'name' => $filter1Name,
                            ],
                            $filter2Name => [
                                'order' => 5,
                            ],
                        ],
                    ],
                    'columns' => [
                        $filter1Name => [
                            'order' => 1,
                        ],
                        $filter2Name => [
                            'order' => 2,
                        ],
                    ],
                ],
                'expectedFilters' => [
                    $filter1Name => [
                        'label'    => null,
                        'name'     => $filter1Name,
                        'order'    => 1,
                        'disabled' => null,
                    ],
                    $filter2Name => [
                        'label'    => null,
                        'order'    => 5,
                        'disabled' => null,
                    ],
                ],
            ],
            'disabled filter'     => [
                'configData'      => [
                    'filters' => [
                        'columns' => [
                            $filter1Name => [
                                'name' => $filter1Name,
                            ],
                            $filter2Name => [
                                'disabled' => true,
                            ],
                        ],
                    ],
                    'columns' => [
                        $filter1Name => [
                            'order' => 1,
                        ],
                        $filter2Name => [
                            'order' => 2,
                        ],
                    ],
                ],
                'expectedFilters' => [
                    $filter1Name => [
                        'label'    => null,
                        'name'     => $filter1Name,
                        'order'    => 1,
                        'disabled' => null,
                    ],
                ],
            ],
            'disabled column'     => [
                'configData'      => [
                    'filters' => [
                        'columns' => [
                            $filter1Name => [
                                'name' => $filter1Name,
                            ],
                            $filter2Name => [
                            ],
                        ],
                    ],
                    'columns' => [
                        $filter1Name => [
                            'order' => 1,
                        ],
                        $filter2Name => [
                            'disabled' => true,
                        ],
                    ],
                ],
                'expectedFilters' => [
                    $filter1Name => [
                        'label'    => null,
                        'name'     => $filter1Name,
                        'order'    => 1,
                        'disabled' => null,
                    ],
                ],
            ],
            'check order'         => [
                'configData'      => [
                    'filters' => [
                        'columns' => [
                            $filter1Name => [
                                'name' => $filter1Name,
                            ],
                            $filter2Name => [
                                'order' => 1,
                            ],
                        ],
                    ],
                    'columns' => [
                        $filter1Name => [
                            'order' => 2,
                        ],
                    ],
                ],
                'expectedFilters' => [
                    $filter2Name => [
                        'label'    => null,
                        'order'    => 1,
                        'disabled' => null,
                    ],
                    $filter1Name => [
                        'label'    => null,
                        'name'     => $filter1Name,
                        'order'    => 2,
                        'disabled' => null,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider testGetDatagridFiltersCombinedProvider
     */
    public function testGetDatagridFiltersCombined(array $configData, array $expectedFilters): void
    {
        $gridConfig    = $this->getGridConfig(OrmDatasource::TYPE, $configData);
        $assertFilters = [];
        $mockWith      = [];
        $mockReturn    = [];
        foreach ($expectedFilters as $filterName => $filterConfig) {
            $filter = $this->createMock(FilterInterface::class);
            $assertFilters[$filterName] = $filter;
            $mockWith[] = [$filterName, $filterConfig];
            $mockReturn[] = $filter;
        }

        $this->filterFactory->method('createFilter')
            ->withConsecutive(... $mockWith)
            ->willReturnOnConsecutiveCalls(... $mockReturn);

        $this->assertEquals($assertFilters, $this->provider->getDatagridFilters($gridConfig));
    }

    private function getGridConfig(string $datasourceType, array $gridConfig = []): DatagridConfiguration
    {
        $configuration = DatagridConfiguration::createNamed('sample_grid', $gridConfig);
        $configuration->setDatasourceType($datasourceType);

        return $configuration;
    }
}
