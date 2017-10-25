<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Sorter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
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
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
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
                            'disableNotSelectedOption' => false
                        ],
                    ],
                    'initialState' => ['sorters' => []],
                    'state' => ['sorters' => []],
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
                            'disableNotSelectedOption' => false
                        ],
                    ],
                    'initialState' => ['sorters' => []],
                    'state' => ['sorters' => []],
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
                            'disableNotSelectedOption' => false
                        ],
                    ],
                    'initialState' => ['sorters' => []],
                    'state' => ['sorters' => []],
                ]
            ],
            'toolbar with disable not selected option' => [
                'sorters' => [
                    'columns' => [
                        'name' => ['type' => 'string'],
                    ],
                    'default' => [
                        'name' => 'DESC'
                    ],
                    'disable_not_selected_option' => true,
                    'toolbar_sorting' => true,
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
                        'sorters' => [
                            'name' => 'DESC'
                        ]
                    ],
                    'state' => [
                        'sorters' => [
                            'name' => 'DESC'
                        ]
                    ],
                ]
            ],
            'toolbar with disable not selected option and disable default sorting' => [
                'sorters' => [
                    'columns' => [
                        'name' => ['type' => 'string'],
                    ],
                    'default' => [
                        'name' => 'DESC'
                    ],
                    'disable_not_selected_option' => true,
                    'disable_default_sorting' => true,
                    'toolbar_sorting' => true,
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
                    'columns' => [
                        'name' => ['type' => 'string'],
                    ],
                    'disable_default_sorting' => true,
                    'toolbar_sorting' => true,
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

    /**
     * @dataProvider visitMetadataUnknownColumnDataProvider
     * @param array $sorters
     * @param array $columns
     * @param string $expectedMessage
     */
    public function testVisitMetadataUnknownColumn(array $sorters, array $columns, $expectedMessage)
    {
        $this->expectException('\Oro\Bundle\DataGridBundle\Exception\LogicException');
        $this->expectExceptionMessage($expectedMessage);
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

    public function testVisitDatasourceWithoutDefaultSorting()
    {
        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with('Test\Entity')
            ->willReturn($metadata);
        $metadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $qb = new QueryBuilder($em);
        $qb->select('e.id')->from('Test\Entity', 'e');

        $datasource = $this->getMockBuilder(OrmDatasource::class)
            ->disableOriginalConstructor()
            ->getMock();
        $datasource->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $this->extension->setParameters(new ParameterBag());
        $this->extension->visitDatasource(
            DatagridConfiguration::create(['sorters' => ['columns' => []]]),
            $datasource
        );

        self::assertEquals(
            'SELECT e.id FROM Test\Entity e ORDER BY e.id ASC',
            $qb->getDQL()
        );
    }

    public function testVisitDatasourceWhenQueryAlreadyHasOrderBy()
    {
        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects(self::never())
            ->method('getClassMetadata');

        $qb = new QueryBuilder($em);
        $qb->select('e.id')->from('Test\Entity', 'e')->addOrderBy('e.name');

        $datasource = $this->getMockBuilder(OrmDatasource::class)
            ->disableOriginalConstructor()
            ->getMock();
        $datasource->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $this->extension->setParameters(new ParameterBag());
        $this->extension->visitDatasource(
            DatagridConfiguration::create(['sorters' => ['columns' => []]]),
            $datasource
        );

        self::assertEquals(
            'SELECT e.id FROM Test\Entity e ORDER BY e.name ASC',
            $qb->getDQL()
        );
    }

    public function testVisitDatasourceWithoutDefaultSortingForEmptyQuery()
    {
        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects(self::never())
            ->method('getClassMetadata');

        $qb = new QueryBuilder($em);

        $datasource = $this->getMockBuilder(OrmDatasource::class)
            ->disableOriginalConstructor()
            ->getMock();
        $datasource->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $this->extension->setParameters(new ParameterBag());
        $this->extension->visitDatasource(
            DatagridConfiguration::create(['sorters' => ['columns' => []]]),
            $datasource
        );

        self::assertEquals(
            [],
            $qb->getDQLPart('orderBy')
        );
    }

    public function testVisitDatasourceWithoutDefaultSortingAndGroupBy()
    {
        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects(self::never())
            ->method('getClassMetadata');

        $qb = new QueryBuilder($em);
        $qb->select('e.id')->from('Test\Entity', 'e')->groupBy('e.name');

        $datasource = $this->getMockBuilder(OrmDatasource::class)
            ->disableOriginalConstructor()
            ->getMock();
        $datasource->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $this->extension->setParameters(new ParameterBag());
        $this->extension->visitDatasource(
            DatagridConfiguration::create(['sorters' => ['columns' => []]]),
            $datasource
        );

        self::assertEquals(
            'SELECT e.id FROM Test\Entity e GROUP BY e.name ORDER BY e.name ASC',
            $qb->getDQL()
        );
    }

    public function testVisitDatasourceWithoutDefaultSortingAndMultipleGroupBy()
    {
        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects(self::never())
            ->method('getClassMetadata');

        $qb = new QueryBuilder($em);
        $qb->select('e.id')->from('Test\Entity', 'e')->addGroupBy('e.id')->addGroupBy('e.name');

        $datasource = $this->getMockBuilder(OrmDatasource::class)
            ->disableOriginalConstructor()
            ->getMock();
        $datasource->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $this->extension->setParameters(new ParameterBag());
        $this->extension->visitDatasource(
            DatagridConfiguration::create(['sorters' => ['columns' => []]]),
            $datasource
        );

        self::assertEquals(
            'SELECT e.id FROM Test\Entity e GROUP BY e.id, e.name ORDER BY e.id ASC',
            $qb->getDQL()
        );
    }
}
