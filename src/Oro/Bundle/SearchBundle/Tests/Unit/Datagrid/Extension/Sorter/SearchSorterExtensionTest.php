<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Extension\Sorter;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\Sorter\Configuration;
use Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Sorter\AbstractSorterExtensionTestCase;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Datagrid\Extension\Sorter\SearchSorterExtension;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class SearchSorterExtensionTest extends AbstractSorterExtensionTestCase
{
    /**
     * @var SearchSorterExtension
     */
    protected $extension;

    public function setUp()
    {
        parent::setUp();

        $this->extension = new SearchSorterExtension($this->sortersStateProvider);
    }

    /**
     * @param string $configDataType A valid configuration datatype (eg. string or integer)
     *
     * @dataProvider visitDatasourceWithValidTypeProvider
     */
    public function testVisitDatasourceWithValidType($configDataType)
    {
        $config = DatagridConfiguration::create([
            Configuration::SORTERS_KEY => [
                Configuration::DEFAULT_SORTERS_KEY => [
                    'testColumn' => 'ASC'
                ],
                Configuration::COLUMNS_KEY => [
                    'testColumn' => [
                        'data_name' => 'testColumn',
                        'type'      => $configDataType,
                    ]
                ]
            ]
        ]);

        $this->sortersStateProvider
            ->expects($this->once())
            ->method('getStateFromParameters')
            ->willReturn(['testColumn' => 'ASC']);

        $mockQuery = $this->createMock(SearchQueryInterface::class);

        $mockDatasource = $this->createMock(SearchDatasource::class);

        $mockDatasource
            ->expects($this->once())
            ->method('getSearchQuery')
            ->willReturn($mockQuery);

        $mockParameterBag = $this->createMock(ParameterBag::class);
        $this->extension->setParameters($mockParameterBag);

        $this->extension->visitDatasource($config, $mockDatasource);
    }

    /**
     * @return array
     */
    public function visitDatasourceWithValidTypeProvider()
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

    /**
     * @expectedException \Oro\Bundle\SearchBundle\Exception\InvalidConfigurationException
     */
    public function testVisitDatasourceWithInvalidType()
    {
        $config = DatagridConfiguration::create([
            Configuration::SORTERS_KEY => [
                Configuration::DEFAULT_SORTERS_KEY => [
                    'testColumn' => 'ASC'
                ],
                Configuration::COLUMNS_KEY => [
                    'testColumn' => [
                        'data_name' => 'testColumn',
                        'type'      => 'this_will_not_be_a_valid_type',
                    ]
                ]
            ]
        ]);

        $this->sortersStateProvider
            ->expects($this->once())
            ->method('getStateFromParameters')
            ->willReturn(['testColumn' => 'ASC']);

        $mockDatasource = $this->getMockBuilder(SearchDatasource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockParameterBag = $this->getMockBuilder(ParameterBag::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension->setParameters($mockParameterBag);
        $this->extension->visitDatasource($config, $mockDatasource);
    }

    public function testVisitDatasourceWithDefaultSorterAndDefaultSortingIsNotDisabled()
    {
        $config = DatagridConfiguration::create([
            Configuration::SORTERS_KEY => [
                Configuration::COLUMNS_KEY                 => [
                    'testColumn' => [
                        'data_name' => 'testColumn',
                        'type'      => 'string',
                    ]
                ],
                Configuration::DEFAULT_SORTERS_KEY         => [
                    'testColumn' => 'ASC',
                ]
            ],
        ]);

        $this->sortersStateProvider
            ->expects($this->once())
            ->method('getStateFromParameters')
            ->willReturn(['testColumn' => 'ASC']);

        $mockQuery = $this->getMockBuilder(SearchQueryInterface::class)
            ->getMock();

        $mockDatasource = $this->getMockBuilder(SearchDatasource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockDatasource
            ->expects($this->once())
            ->method('getSearchQuery')
            ->willReturn($mockQuery);

        $mockParameterBag = $this->getMockBuilder(ParameterBag::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension->setParameters($mockParameterBag);

        $this->extension->visitDatasource($config, $mockDatasource);
    }

    public function testVisitDatasourceWithNoDefaultSorterAndDisableDefaultSorting()
    {
        $this->sortersStateProvider
            ->expects($this->any())
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

        list($mockDatasource, $mockParameterBag) = $this->getDependenciesMocks();

        $this->extension->setParameters($mockParameterBag);
        $this->extension->visitDatasource($config, $mockDatasource);
    }

    public function testVisitDatasourceWithDefaultSorterAndDisableDefaultSorting()
    {
        $this->sortersStateProvider
            ->expects($this->any())
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

        list($mockDatasource, $mockParameterBag) = $this->getDependenciesMocks();

        $this->extension->setParameters($mockParameterBag);
        $this->extension->visitDatasource($config, $mockDatasource);
    }

    /**
     * @return array
     */
    private function getDependenciesMocks()
    {
        $mockQuery = $this->getMockBuilder(SearchQueryInterface::class)
            ->getMock();

        $mockDatasource = $this->getMockBuilder(SearchDatasource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockDatasource
            ->expects($this->never())
            ->method('getSearchQuery')
            ->willReturn($mockQuery);

        $mockParameterBag = $this->getMockBuilder(ParameterBag::class)
            ->disableOriginalConstructor()
            ->getMock();

        return [$mockDatasource, $mockParameterBag];
    }
}
