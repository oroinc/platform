<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Extension\Sorter;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\Sorter\Configuration;
use Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Sorter\AbstractSorterExtensionTestCase;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Datagrid\Extension\Sorter\SearchSorterExtension;
use Oro\Bundle\SearchBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class SearchSorterExtensionTest extends AbstractSorterExtensionTestCase
{
    /** @var SearchSorterExtension */
    protected $extension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension = new SearchSorterExtension($this->sortersStateProvider, $this->resolver);
    }

    /**
     * @dataProvider visitDatasourceWithValidTypeProvider
     */
    public function testVisitDatasourceWithValidType(string $configDataType)
    {
        $this->configureResolver();
        $config = DatagridConfiguration::create([
            Configuration::SORTERS_KEY => [
                Configuration::DEFAULT_SORTERS_KEY => [
                    'testColumn' => 'ASC'
                ],
                Configuration::COLUMNS_KEY         => [
                    'testColumn' => [
                        'data_name' => 'testColumn',
                        'type'      => $configDataType,
                    ]
                ]
            ]
        ]);

        $this->sortersStateProvider->expects($this->once())
            ->method('getStateFromParameters')
            ->willReturn(['testColumn' => 'ASC']);

        $query = $this->createMock(SearchQueryInterface::class);

        $datasource = $this->createMock(SearchDatasource::class);
        $datasource->expects($this->once())
            ->method('getSearchQuery')
            ->willReturn($query);

        $parameterBag = $this->createMock(ParameterBag::class);
        $this->extension->setParameters($parameterBag);

        $this->extension->visitDatasource($config, $datasource);
    }

    public function visitDatasourceWithValidTypeProvider(): array
    {
        return [
            'string'  => [
                'configDataType' => 'string',
            ],
            'integer' => [
                'configDataType' => 'integer',
            ],
        ];
    }

    public function testVisitDatasourceWithInvalidType()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->configureResolver();
        $config = DatagridConfiguration::create([
            Configuration::SORTERS_KEY => [
                Configuration::DEFAULT_SORTERS_KEY => [
                    'testColumn' => 'ASC'
                ],
                Configuration::COLUMNS_KEY         => [
                    'testColumn' => [
                        'data_name' => 'testColumn',
                        'type'      => 'this_will_not_be_a_valid_type',
                    ]
                ]
            ]
        ]);

        $this->sortersStateProvider->expects($this->once())
            ->method('getStateFromParameters')
            ->willReturn(['testColumn' => 'ASC']);

        $datasource = $this->createMock(SearchDatasource::class);
        $parameterBag = $this->createMock(ParameterBag::class);

        $this->extension->setParameters($parameterBag);
        $this->extension->visitDatasource($config, $datasource);
    }

    public function testVisitDatasourceWithDefaultSorterAndDefaultSortingIsNotDisabled()
    {
        $this->configureResolver();
        $config = DatagridConfiguration::create([
            Configuration::SORTERS_KEY => [
                Configuration::COLUMNS_KEY         => [
                    'testColumn' => [
                        'data_name' => 'testColumn',
                        'type'      => 'string',
                    ]
                ],
                Configuration::DEFAULT_SORTERS_KEY => [
                    'testColumn' => 'ASC',
                ]
            ],
        ]);

        $this->sortersStateProvider->expects($this->once())
            ->method('getStateFromParameters')
            ->willReturn(['testColumn' => 'ASC']);

        $query = $this->createMock(SearchQueryInterface::class);

        $datasource = $this->createMock(SearchDatasource::class);
        $datasource->expects($this->once())
            ->method('getSearchQuery')
            ->willReturn($query);

        $parameterBag = $this->createMock(ParameterBag::class);
        $this->extension->setParameters($parameterBag);

        $this->extension->visitDatasource($config, $datasource);
    }

    public function testVisitDatasourceWithNoDefaultSorterAndDisableDefaultSorting()
    {
        $this->configureResolver();
        $this->sortersStateProvider->expects($this->any())
            ->method('getStateFromParameters')
            ->willReturn([]);

        $config = DatagridConfiguration::create([
            Configuration::SORTERS_KEY => [
                Configuration::COLUMNS_KEY                 => [
                    'testColumn' => [
                        'data_name' => 'testColumn',
                        'type'      => 'string',
                    ]
                ],
                Configuration::DISABLE_DEFAULT_SORTING_KEY => true,
            ],
        ]);

        $datasource = $this->createMock(SearchDatasource::class);
        $datasource->expects($this->never())
            ->method('getSearchQuery')
            ->willReturn($this->createMock(SearchQueryInterface::class));

        $parameterBag = $this->createMock(ParameterBag::class);

        $this->extension->setParameters($parameterBag);
        $this->extension->visitDatasource($config, $datasource);
    }

    public function testVisitDatasourceWithDefaultSorterAndDisableDefaultSorting()
    {
        $this->configureResolver();
        $this->sortersStateProvider->expects($this->any())
            ->method('getStateFromParameters')
            ->willReturn([]);

        $config = DatagridConfiguration::create([
            Configuration::SORTERS_KEY => [
                Configuration::COLUMNS_KEY                 => [
                    'testColumn' => [
                        'data_name' => 'testColumn',
                        'type'      => 'string',
                    ]
                ],
                Configuration::DISABLE_DEFAULT_SORTING_KEY => true,
                Configuration::DEFAULT_SORTERS_KEY         => [
                    'testColumn' => 'ASC',
                ]
            ],
        ]);

        $datasource = $this->createMock(SearchDatasource::class);
        $datasource->expects($this->never())
            ->method('getSearchQuery')
            ->willReturn($this->createMock(SearchQueryInterface::class));

        $parameterBag = $this->createMock(ParameterBag::class);

        $this->extension->setParameters($parameterBag);
        $this->extension->visitDatasource($config, $datasource);
    }
}
