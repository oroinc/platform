<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Datagrid\Extension\Sorter;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\Sorter\Configuration;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\SearchBundle\Datagrid\Extension\Sorter\SearchSorterExtension;

class SearchSorterExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SearchSorterExtension
     */
    protected $sorter;

    public function setUp()
    {
        $this->sorter = new SearchSorterExtension();
    }

    /**
     * @param array $input
     * @param array $expected
     *
     * @dataProvider setParametersDataProvider
     */
    public function testSetParameters(array $input, array $expected)
    {
        $this->sorter->setParameters(new ParameterBag($input));
        $this->assertEquals($expected, $this->sorter->getParameters()->all());
    }

    /**
     * @return array
     */
    public function setParametersDataProvider()
    {
        return [
            'empty'    => [
                'input'    => [],
                'expected' => [],
            ],
            'regular'  => [
                'input'    => [
                    SearchSorterExtension::SORTERS_ROOT_PARAM => [
                        'firstName' => SearchSorterExtension::DIRECTION_ASC,
                        'lastName'  => SearchSorterExtension::DIRECTION_DESC,
                    ]
                ],
                'expected' => [
                    SearchSorterExtension::SORTERS_ROOT_PARAM => [
                        'firstName' => SearchSorterExtension::DIRECTION_ASC,
                        'lastName'  => SearchSorterExtension::DIRECTION_DESC,
                    ]
                ]
            ],
            'minified' => [
                'input'    => [
                    ParameterBag::MINIFIED_PARAMETERS => [
                        SearchSorterExtension::MINIFIED_SORTERS_PARAM => [
                            'firstName' => '-1',
                            'lastName'  => '1',
                        ]
                    ]
                ],
                'expected' => [
                    ParameterBag::MINIFIED_PARAMETERS         => [
                        SearchSorterExtension::MINIFIED_SORTERS_PARAM => [
                            'firstName' => '-1',
                            'lastName'  => '1',
                        ]
                    ],
                    SearchSorterExtension::SORTERS_ROOT_PARAM => [
                        'firstName' => SearchSorterExtension::DIRECTION_ASC,
                        'lastName'  => SearchSorterExtension::DIRECTION_DESC,
                    ]
                ]
            ],
        ];
    }

    /**
     * @param array $sorters
     * @param array $columns
     * @param array $expectedData
     *
     * @dataProvider visitMetadataDataProvider
     */
    public function testVisitMetadata(array $sorters, array $columns, array $expectedData)
    {
        $config = DatagridConfiguration::create([Configuration::SORTERS_KEY => $sorters]);

        $data = MetadataObject::create([Configuration::COLUMNS_KEY => $columns]);
        $this->sorter->setParameters(new ParameterBag());
        $this->sorter->visitMetadata($config, $data);
        $this->assertEquals($expectedData, $data->toArray());
    }

    /**
     * @return array
     */
    public function visitMetadataDataProvider()
    {
        return [
            'sortable' => [
                Configuration::SORTERS_KEY => [
                    Configuration::COLUMNS_KEY => [
                        'name' => [],
                    ],
                ],
                Configuration::COLUMNS_KEY => [
                    ['name' => 'name'],
                    ['name' => 'createdAt'],
                ],
                'expectedData'             => [
                    Configuration::COLUMNS_KEY => [
                        [
                            'name'     => 'name',
                            'sortable' => true,
                        ],
                        ['name' => 'createdAt']
                    ],
                    'options'                  => [
                        'multipleSorting' => false,
                        'toolbarOptions'  => [
                            'addSorting' => false,
                        ],
                    ],
                    'initialState'             => [Configuration::SORTERS_KEY => ['name' => 'ASC',]],
                    'state'                    => [Configuration::SORTERS_KEY => ['name' => 'ASC',]],
                ]
            ],
            'multiple' => [
                Configuration::SORTERS_KEY => [
                    Configuration::COLUMNS_KEY   => [
                        'name' => [],
                    ],
                    Configuration::MULTISORT_KEY => true,
                ],
                Configuration::COLUMNS_KEY => [
                    ['name' => 'name'],
                    ['name' => 'createdAt'],
                ],
                'expectedData'             => [
                    Configuration::COLUMNS_KEY => [
                        [
                            'name'     => 'name',
                            'sortable' => true,
                        ],
                        ['name' => 'createdAt']
                    ],
                    'options'                  => [
                        'multipleSorting' => true,
                        'toolbarOptions'  => [
                            'addSorting' => false,
                        ],
                    ],
                    'initialState'             => [Configuration::SORTERS_KEY => ['name' => 'ASC',]],
                    'state'                    => [Configuration::SORTERS_KEY => ['name' => 'ASC',]],
                ]
            ],
            'toolbar'  => [
                Configuration::SORTERS_KEY => [
                    Configuration::COLUMNS_KEY         => [
                        'name' => ['type' => 'string'],
                        'age'  => [],
                    ],
                    Configuration::TOOLBAR_SORTING_KEY => true,
                ],
                Configuration::COLUMNS_KEY => [
                    ['name' => 'name'],
                    ['name' => 'age'],
                    ['name' => 'createdAt'],
                ],
                'expectedData'             => [
                    Configuration::COLUMNS_KEY => [
                        [
                            'name'        => 'name',
                            'sortable'    => true,
                            'sortingType' => 'string',
                        ],
                        [
                            'name'     => 'age',
                            'sortable' => true,
                        ],
                        ['name' => 'createdAt']
                    ],
                    'options'                  => [
                        'multipleSorting' => false,
                        'toolbarOptions'  => [
                            'addSorting' => true,
                        ],
                    ],
                    'initialState'             => [Configuration::SORTERS_KEY => ['name' => 'ASC',]],
                    'state'                    => [Configuration::SORTERS_KEY => ['name' => 'ASC',]],
                ]
            ]
        ];
    }

    /**
     * @dataProvider visitMetadataUnknownColumnDataProvider
     * @param array  $sorters
     * @param array  $columns
     * @param string $expectedMessage
     */
    public function testVisitMetadataUnknownColumn(array $sorters, array $columns, $expectedMessage)
    {
        $this->setExpectedException('\Oro\Bundle\DataGridBundle\Exception\LogicException', $expectedMessage);
        $config = DatagridConfiguration::create([Configuration::SORTERS_KEY => $sorters]);

        $data = MetadataObject::create([Configuration::COLUMNS_KEY => $columns]);
        $this->sorter->setParameters(new ParameterBag());
        $this->sorter->visitMetadata($config, $data);
    }

    /**
     * @return array
     */
    public function visitMetadataUnknownColumnDataProvider()
    {
        return [
            'unknown column'        => [
                Configuration::SORTERS_KEY => [
                    Configuration::COLUMNS_KEY => [
                        'unknown' => [],
                        'age'     => [],
                    ],
                ],
                Configuration::COLUMNS_KEY => [
                    ['name' => 'age'],
                    ['name' => 'createdAt'],
                ],
                'expectedMessage'          => 'Could not found column(s) "unknown" for sorting',
            ],
            'unknown single column' => [
                Configuration::SORTERS_KEY => [
                    Configuration::COLUMNS_KEY => [
                        'unknown' => [],
                    ],
                ],
                Configuration::COLUMNS_KEY => [
                    ['name' => 'age'],
                    ['name' => 'createdAt'],
                ],
                'expectedMessage'          => 'Could not found column(s) "unknown" for sorting',
            ],
        ];
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
                Configuration::COLUMNS_KEY => [
                    'testColumn' => [
                        'data_name' => 'testColumn',
                        'type'      => $configDataType,
                    ]
                ]
            ]
        ]);

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
        $this->sorter->setParameters($mockParameterBag);

        $this->sorter->visitDatasource($config, $mockDatasource);
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
                Configuration::COLUMNS_KEY => [
                    'testColumn' => [
                        'data_name' => 'testColumn',
                        'type'      => 'this_will_not_be_a_valid_type',
                    ]
                ]
            ]
        ]);

        $mockDatasource = $this->getMockBuilder(SearchDatasource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockParameterBag = $this->getMockBuilder(ParameterBag::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sorter->setParameters($mockParameterBag);
        $this->sorter->visitDatasource($config, $mockDatasource);
    }

    public function testVisitDatasourceWithDisableDefaultSorting()
    {
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

        $this->sorter->setParameters($mockParameterBag);
        $this->sorter->visitDatasource($config, $mockDatasource);
    }

    public function testVisitDatasourceWithDefaultSorterAndDisableDefaultSorting()
    {
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

        $this->sorter->setParameters($mockParameterBag);
        $this->sorter->visitDatasource($config, $mockDatasource);
    }

    public function testVisitDatasourceThrowsExceptionWhenInvalidDefaultSortersApplied()
    {
        $config = DatagridConfiguration::create([
            Configuration::SORTERS_KEY => [
                Configuration::COLUMNS_KEY         => [
                    'testColumn' => [
                        'data_name' => 'testColumn',
                        'type'      => 'string',
                    ]
                ],
                Configuration::DEFAULT_SORTERS_KEY => [
                    'non-existing' => 'ASC',
                ],
            ],
        ]);

        list($mockDatasource, $mockParameterBag) = $this->getDependenciesMocks();

        $this->sorter->setParameters($mockParameterBag);

        $this->setExpectedException(LogicException::class);
        $this->sorter->visitDatasource($config, $mockDatasource);
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
