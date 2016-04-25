<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Sorter;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\Sorter\OrmSorterExtension;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;

class OrmSorterExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OrmSorterExtension
     */
    protected $extension;

    public function setUp()
    {
        $this->extension = new OrmSorterExtension();
    }

    /**
     * @param array $input
     * @param array $expected
     * @dataProvider setParametersDataProvider
     */
    public function testSetParameters(array $input, array $expected)
    {
        $this->extension->setParameters(new ParameterBag($input));
        $this->assertEquals($expected, $this->extension->getParameters()->all());
    }

    /**
     * @return array
     */
    public function setParametersDataProvider()
    {
        return [
            'empty' => [
                'input' => [],
                'expected' => [],
            ],
            'regular' => [
                'input' => [
                    OrmSorterExtension::SORTERS_ROOT_PARAM => [
                        'firstName' => OrmSorterExtension::DIRECTION_ASC,
                        'lastName' => OrmSorterExtension::DIRECTION_DESC,
                    ]
                ],
                'expected' => [
                    OrmSorterExtension::SORTERS_ROOT_PARAM => [
                        'firstName' => OrmSorterExtension::DIRECTION_ASC,
                        'lastName' => OrmSorterExtension::DIRECTION_DESC,
                    ]
                ]
            ],
            'minified' => [
                'input' => [
                    ParameterBag::MINIFIED_PARAMETERS => [
                        OrmSorterExtension::MINIFIED_SORTERS_PARAM => [
                            'firstName' => '-1',
                            'lastName' => '1',
                        ]
                    ]
                ],
                'expected' => [
                    ParameterBag::MINIFIED_PARAMETERS => [
                        OrmSorterExtension::MINIFIED_SORTERS_PARAM => [
                            'firstName' => '-1',
                            'lastName' => '1',
                        ]
                    ],
                    OrmSorterExtension::SORTERS_ROOT_PARAM => [
                        'firstName' => OrmSorterExtension::DIRECTION_ASC,
                        'lastName' => OrmSorterExtension::DIRECTION_DESC,
                    ]
                ]
            ],
        ];
    }

    /**
     * @dataProvider visitMetadataDataProvider
     * @param array $sorters
     * @param array $columns
     * @param array $expectedData
     */
    public function testVisitMetadata(array $sorters, array $columns, array $expectedData)
    {
        $config = DatagridConfiguration::create(['sorters' => $sorters]);

        $data = MetadataObject::create(['columns' => $columns]);
        $this->extension->setParameters(new ParameterBag());
        $this->extension->visitMetadata($config, $data);
        $this->assertEquals($expectedData, $data->toArray());
    }

    /**
     * @return array
     */
    public function visitMetadataDataProvider()
    {
        return [
            'sortable' => [
                'sorters' => [
                    'columns' => [
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
                        ],
                    ],
                    'initialState' => ['sorters' => ['name' => 'ASC',]],
                    'state' => ['sorters' => ['name' => 'ASC',]],
                ]
            ],
            'multiple' => [
                'sorters' => [
                    'columns' => [
                        'name' => [],
                    ],
                    'multiple_sorting' => true,
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
                        ],
                    ],
                    'initialState' => ['sorters' => ['name' => 'ASC',]],
                    'state' => ['sorters' => ['name' => 'ASC',]],
                ]
            ],
            'toolbar' => [
                'sorters' => [
                    'columns' => [
                        'name' => ['type' => 'string'],
                        'age' => [],
                    ],
                    'toolbar_sorting' => true,
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
                        ],
                    ],
                    'initialState' => ['sorters' => ['name' => 'ASC',]],
                    'state' => ['sorters' => ['name' => 'ASC',]],
                ]
            ]
        ];
    }

    /**
     * @dataProvider visitMetadataUnknownColumnDataProvider
     * @param array $sorters
     * @param array $columns
     * @param string $expectedMessage
     */
    public function testVisitMetadataUnknownColumn(array $sorters, array $columns, $expectedMessage)
    {
        $this->setExpectedException('\Oro\Bundle\DataGridBundle\Exception\LogicException', $expectedMessage);
        $config = DatagridConfiguration::create(['sorters' => $sorters]);

        $data = MetadataObject::create(['columns' => $columns]);
        $this->extension->setParameters(new ParameterBag());
        $this->extension->visitMetadata($config, $data);
    }

    /**
     * @return array
     */
    public function visitMetadataUnknownColumnDataProvider()
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
