<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Sorter;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\Sorter\AbstractSorterExtension;
use Oro\Bundle\DataGridBundle\Extension\Sorter\Configuration;
use Oro\Bundle\DataGridBundle\Provider\State\SortersStateProvider;

abstract class AbstractSorterExtensionTestCase extends \PHPUnit\Framework\TestCase
{
    /** @var SortersStateProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $sortersStateProvider;

    /** @var AbstractSorterExtension */
    protected $extension;

    public function setUp()
    {
        $this->sortersStateProvider = $this->createMock(SortersStateProvider::class);
    }

    /**
     * @dataProvider visitMetadataDataProvider
     *
     * @param array $sorters
     * @param array $columns
     * @param array $expectedData
     */
    public function testVisitMetadata(array $sorters, array $columns, array $expectedData): void
    {
        $this->sortersStateProvider
            ->expects($this->once())
            ->method('getState')
            ->willReturn([]);
        $this->sortersStateProvider
            ->expects(self::once())
            ->method('getDefaultState')
            ->willReturn([]);

        $config = DatagridConfiguration::create([Configuration::SORTERS_KEY => $sorters]);

        $data = MetadataObject::create([Configuration::COLUMNS_KEY => $columns]);
        $this->extension->setParameters(new ParameterBag());
        $this->extension->visitMetadata($config, $data);

        self::assertEquals($expectedData, $data->toArray());
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function visitMetadataDataProvider(): array
    {
        return [
            'sortable' => [
                'sorters' => [
                    Configuration::COLUMNS_KEY => [
                        'name' => [],
                    ],
                ],
                'columns' => [
                    ['name' => 'name'],
                    ['name' => 'createdAt'],
                ],
                'expectedData' => [
                    'columns' => [
                        [
                            'name' => 'name',
                            'sortable' => true,
                        ],
                        ['name' => 'createdAt']
                    ],
                    'options' => [
                        'multipleSorting' => false,
                        'toolbarOptions' => [
                            'addSorting' => false,
                            'disableNotSelectedOption' => false
                        ],
                    ],
                    'initialState' => ['sorters' => []],
                    'state' => ['sorters' => []],
                ]
            ],
            'multiple' => [
                'sorters' => [
                    Configuration::COLUMNS_KEY => [
                        'name' => [],
                    ],
                    Configuration::MULTISORT_KEY => true,
                ],
                'columns' => [
                    ['name' => 'name'],
                    ['name' => 'createdAt'],
                ],
                'expectedData' => [
                    'columns' => [
                        [
                            'name' => 'name',
                            'sortable' => true,
                        ],
                        ['name' => 'createdAt']
                    ],
                    'options' => [
                        'multipleSorting' => true,
                        'toolbarOptions' => [
                            'addSorting' => false,
                            'disableNotSelectedOption' => false
                        ],
                    ],
                    'initialState' => ['sorters' => []],
                    'state' => ['sorters' => []],
                ]
            ],
            'toolbar' => [
                'sorters' => [
                    Configuration::COLUMNS_KEY => [
                        'name' => ['type' => 'string'],
                        'age' => [],
                    ],
                    Configuration::TOOLBAR_SORTING_KEY => true,
                ],
                'columns' => [
                    ['name' => 'name'],
                    ['name' => 'age'],
                    ['name' => 'createdAt'],
                ],
                'expectedData' => [
                    'columns' => [
                        [
                            'name' => 'name',
                            'sortable' => true,
                            'sortingType' => 'string',
                        ],
                        [
                            'name' => 'age',
                            'sortable' => true,
                        ],
                        ['name' => 'createdAt']
                    ],
                    'options' => [
                        'multipleSorting' => false,
                        'toolbarOptions' => [
                            'addSorting' => true,
                            'disableNotSelectedOption' => false
                        ],
                    ],
                    'initialState' => ['sorters' => []],
                    'state' => ['sorters' => []],
                ]
            ],
            'toolbar with disable not selected option' => [
                'sorters' => [
                    Configuration::COLUMNS_KEY => [
                        'name' => ['type' => 'string'],
                    ],
                    Configuration::DEFAULT_SORTERS_KEY => [
                        'name' => 'DESC'
                    ],
                    Configuration::DISABLE_NOT_SELECTED_OPTION_KEY => true,
                    Configuration::TOOLBAR_SORTING_KEY => true,
                ],
                'columns' => [
                    ['name' => 'name']
                ],
                'expectedData' => [
                    'columns' => [
                        [
                            'name' => 'name',
                            'sortable' => true,
                            'sortingType' => 'string',
                        ]
                    ],
                    'options' => [
                        'multipleSorting' => false,
                        'toolbarOptions' => [
                            'addSorting' => true,
                            'disableNotSelectedOption' => true
                        ],
                    ],
                    'initialState' => [
                        'sorters' => []
                    ],
                    'state' => [
                        'sorters' => []
                    ],
                ]
            ],
            'toolbar with disable not selected option and disable default sorting' => [
                'sorters' => [
                    Configuration::COLUMNS_KEY => [
                        'name' => ['type' => 'string'],
                    ],
                    Configuration::DEFAULT_SORTERS_KEY => [
                        'name' => 'DESC'
                    ],
                    Configuration::DISABLE_NOT_SELECTED_OPTION_KEY => true,
                    Configuration::DISABLE_DEFAULT_SORTING_KEY => true,
                    Configuration::TOOLBAR_SORTING_KEY => true,
                ],
                'columns' => [
                    ['name' => 'name']
                ],
                'expectedData' => [
                    'columns' => [
                        [
                            'name' => 'name',
                            'sortable' => true,
                            'sortingType' => 'string',
                        ]
                    ],
                    'options' => [
                        'multipleSorting' => false,
                        'toolbarOptions' => [
                            'addSorting' => true,
                            'disableNotSelectedOption' => false
                        ],
                    ],
                    'initialState' => ['sorters' => []],
                    'state' => ['sorters' => []],
                ]
            ],
            'toolbar with disable not selected option and with default sortings' => [
                'sorters' => [
                    Configuration::COLUMNS_KEY => [
                        'name' => ['type' => 'string'],
                    ],
                    Configuration::DISABLE_DEFAULT_SORTING_KEY => true,
                    Configuration::TOOLBAR_SORTING_KEY => true,
                ],
                'columns' => [
                    ['name' => 'name']
                ],
                'expectedData' => [
                    'columns' => [
                        [
                            'name' => 'name',
                            'sortable' => true,
                            'sortingType' => 'string',
                        ]
                    ],
                    'options' => [
                        'multipleSorting' => false,
                        'toolbarOptions' => [
                            'addSorting' => true,
                            'disableNotSelectedOption' => false
                        ],
                    ],
                    'initialState' => ['sorters' => []],
                    'state' => ['sorters' => []],
                ]
            ]
        ];
    }

    public function testVisitMetadataStateIsSet(): void
    {
        $config = DatagridConfiguration::create(['sorters' => ['columns' => []]]);
        $datagridParameters = new ParameterBag();

        $this->sortersStateProvider
            ->expects(self::once())
            ->method('getState')
            ->with($config, $datagridParameters)
            ->willReturn($state = ['name' => 'DESC']);

        $this->sortersStateProvider
            ->expects(self::once())
            ->method('getDefaultState')
            ->with($config)
            ->willReturn($initialState = ['name' => 'ASC']);

        $data = MetadataObject::create([]);
        $this->extension->setParameters($datagridParameters);
        $this->extension->visitMetadata($config, $data);

        $expectedData = [
            'options' => [
                'multipleSorting' => false,
                'toolbarOptions' => [
                    'addSorting' => false,
                    'disableNotSelectedOption' => false
                ],
            ],
            'initialState' => ['sorters' => ['name' => 'ASC']],
            'state' => ['sorters' => ['name' => 'DESC']],
        ];

        self::assertEquals($expectedData, $data->toArray());
    }

    /**
     * @dataProvider visitMetadataUnknownColumnDataProvider
     *
     * @param array $sorters
     * @param array $columns
     * @param string $expectedMessage
     */
    public function testVisitMetadataUnknownColumn(array $sorters, array $columns, $expectedMessage): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage($expectedMessage);

        $config = DatagridConfiguration::create(['sorters' => $sorters]);
        $data = MetadataObject::create(['columns' => $columns]);

        $this->extension->setParameters(new ParameterBag());
        $this->extension->visitMetadata($config, $data);
    }

    /**
     * @return array
     */
    public function visitMetadataUnknownColumnDataProvider(): array
    {
        return [
            'unknown column' => [
                'sorters' => [
                    'columns' => [
                        'unknown' => [],
                        'age' => [],
                    ],
                ],
                'columns' => [
                    ['name' => 'age'],
                    ['name' => 'createdAt'],
                ],
                'expectedMessage' => 'Could not found column(s) "unknown" for sorting',
            ],
            'unknown single column' => [
                'sorters' => [
                    'columns' => [
                        'unknown' => [],
                    ],
                ],
                'columns' => [
                    ['name' => 'age'],
                    ['name' => 'createdAt'],
                ],
                'expectedMessage' => 'Could not found column(s) "unknown" for sorting',
            ],
        ];
    }
}
