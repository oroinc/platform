<?php

namespace Oro\Bundle\EntityPaginationBundle\Tests\Unit\Storage;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\EntityPaginationBundle\Storage\EntityPaginationStorage;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Session\Session;

class EntityPaginationStorageTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_NAME = 'stdClass';
    const GRID_NAME   = 'test_grid';
    const FIELD_NAME  = 'id';

    public static $previous_ids = [1, 2, 3, 4, 5];
    public static $current_ids  = [6, 7, 8, 9, 10];
    public static $next_ids     = [11, 12, 13, 14];

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $datagridManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $session;

    /** @var EntityPaginationStorage */
    protected $storage;

    /** @var stdClass */
    protected $entity;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->datagridManager = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Manager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->storage = new EntityPaginationStorage($this->datagridManager, $this->doctrineHelper);
        $this->entity = new \stdClass();
    }

    /**
     * @param $request
     * @param $paginationState
     * @param $expected
     *
     * @dataProvider addDataDataProvider
     */
    public function testAddData($request, $paginationState, $expected)
    {
        if (true === $request) {
            $session = new Session(new MockArraySessionStorage());
            $request = new Request();
            $request->setSession($session);
            $this->storage->setRequest($request);
        }

        $result = $this->storage->addData(self::ENTITY_NAME, self::GRID_NAME, $paginationState);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function addDataDataProvider()
    {
        return [
            'is set request' => [
                'request' => true,
                'pagination_state' => [
                    'state'   => [
                        '_pager' => [
                            '_page' => 2,
                            '_per_page' => 5
                        ],
                        '_sort_by' => [
                            'name' => 'ASC'
                        ],
                        '_filter'  => []
                    ],
                    'current_ids' => self::$current_ids,
                    'total' => 14,
                ],
                'expected' => true
            ],
            'is not set request' => [
                'request' => false,
                'pagination_state' => [
                    'state'   => [
                        '_pager' => [
                            '_page' => 2,
                            '_per_page' => 5
                        ],
                        '_sort_by' => [
                            'name' => 'ASC'
                        ],
                        '_filter'  => []
                    ],
                    'current_ids' => self::$current_ids,
                    'total' => 14,
                ],
                'expected' => false
            ]
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function getNextDataProvider()
    {
        return [
            'in current ids' => [
                'entityId' => 8,
                'source' => [
                    'grid_name' => self::GRID_NAME,
                    'pagination_state' => [
                        'state'   => [
                            '_pager' => [
                                '_page' => 2,
                                '_per_page' => 5
                            ],
                            '_sort_by' => [
                                'name' => 'ASC'
                            ],
                            '_filter'  => []
                        ],
                        'current_ids' => self::$current_ids,
                        'total' => 14,
                        'next_id' => null,
                        'previous_id' => null
                    ]
                ],
                'expected' => 9,
            ],
            'not in storage' => [
                'entityId' => 40,
                'source' => [
                    'grid_name' => self::GRID_NAME,
                    'pagination_state' => [
                        'state'   => [
                            '_pager' => [
                                '_page' => 2,
                                '_per_page' => 5
                            ],
                            '_sort_by' => [
                                'name' => 'ASC'
                            ],
                            '_filter'  => []
                        ],
                        'current_ids' => self::$current_ids,
                        'total' => 14,
                        'next_id' => null,
                        'previous_id' => null
                    ]
                ],
                'expected' => null,
            ],
            'last in current ids with empty next id' => [
                'entityId' => 10,
                'source' => [
                    'grid_name' => self::GRID_NAME,
                    'pagination_state' => [
                        'state'   => [
                            '_pager' => [
                                '_page' => 2,
                                '_per_page' => 5
                            ],
                            '_sort_by' => [
                                'name' => 'ASC'
                            ],
                            '_filter'  => []
                        ],
                        'current_ids' => self::$current_ids,
                        'total' => 14,
                        'next_id' => null,
                        'previous_id' => null
                    ]
                ],
                'expected' => 11,
                'rebuild' => [
                    'page' => 3,
                    'current_ids' => self::$next_ids,
                ]

            ],
            'last in current ids' => [
                'entityId' => 10,
                'source' => [
                    'grid_name' => self::GRID_NAME,
                    'pagination_state' => [
                        'state'   => [
                            '_pager' => [
                                '_page' => 2,
                                '_per_page' => 5
                            ],
                            '_sort_by' => [
                                'name' => 'ASC'
                            ],
                            '_filter'  => []
                        ],
                        'current_ids' => self::$current_ids,
                        'total' => 14,
                        'next_id' => 11,
                        'previous_id' => null
                    ]
                ],
                'expected' => 11
            ],
            'with next and previous' => [
                'entityId' => 7,
                'source' => [
                    'grid_name' => self::GRID_NAME,
                    'pagination_state' => [
                        'state'   => [
                            '_pager' => [
                                '_page' => 2,
                                '_per_page' => 5
                            ],
                            '_sort_by' => [
                                'name' => 'ASC'
                            ],
                            '_filter'  => []
                        ],
                        'current_ids' => self::$current_ids,
                        'total' => 14,
                        'next_id' => 11,
                        'previous_id' => 5
                    ]
                ],
                'expected' => 8
            ],
            'last on last page' => [
                'entityId' => 14,
                'source' => [
                    'grid_name' => self::GRID_NAME,
                    'pagination_state' => [
                        'state'   => [
                            '_pager' => [
                                '_page' => 3,
                                '_per_page' => 5
                            ],
                            '_sort_by' => [
                                'name' => 'ASC'
                            ],
                            '_filter'  => []
                        ],
                        'current_ids' => self::$next_ids,
                        'total' => 14,
                        'next_id' => null,
                        'previous_id' => null
                    ]
                ],
                'expected' => null
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function getPreviousDataProvider()
    {
        return [
            'in current ids' => [
                'entityId' => 8,
                'source' => [
                    'grid_name' => self::GRID_NAME,
                    'pagination_state' => [
                        'state'   => [
                            '_pager' => [
                                '_page' => 2,
                                '_per_page' => 5
                            ],
                            '_sort_by' => [
                                'name' => 'ASC'
                            ],
                            '_filter'  => []
                        ],
                        'current_ids' => self::$current_ids,
                        'total' => 14,
                        'next_id' => null,
                        'previous_id' => null
                    ]
                ],
                'expected' => 7,
            ],
            'not in storage' => [
                'entityId' => 40,
                'source' => [
                    'grid_name' => self::GRID_NAME,
                    'pagination_state' => [
                        'state'   => [
                            '_pager' => [
                                '_page' => 2,
                                '_per_page' => 5
                            ],
                            '_sort_by' => [
                                'name' => 'ASC'
                            ],
                            '_filter'  => []
                        ],
                        'current_ids' => self::$current_ids,
                        'total' => 14,
                        'next_id' => null,
                        'previous_id' => null
                    ]
                ],
                'expected' => null,
            ],
            'first in current ids' => [
                'entityId' => 6,
                'source' => [
                    'grid_name' => self::GRID_NAME,
                    'pagination_state' => [
                        'state'   => [
                            '_pager' => [
                                '_page' => 2,
                                '_per_page' => 5
                            ],
                            '_sort_by' => [
                                'name' => 'ASC'
                            ],
                            '_filter'  => []
                        ],
                        'current_ids' => self::$current_ids,
                        'total' => 14,
                        'next_id' => null,
                        'previous_id' => 5
                    ]
                ],
                'expected' => 5
            ],
            'first in current ids with empty previous id' => [
                'entityId' => 6,
                'source' => [
                    'grid_name' => self::GRID_NAME,
                    'pagination_state' => [
                        'state'   => [
                            '_pager' => [
                                '_page' => 2,
                                '_per_page' => 5
                            ],
                            '_sort_by' => [
                                'name' => 'ASC'
                            ],
                            '_filter'  => []
                        ],
                        'current_ids' => self::$current_ids,
                        'total' => 14,
                        'next_id' => null,
                        'previous_id' => null
                    ]
                ],
                'expected' => 5,
                'rebuild' => [
                    'page' => 1,
                    'current_ids' => self::$previous_ids,
                ]

            ],
            'with next and previous' => [
                'entityId' => 7,
                'source' => [
                    'grid_name' => self::GRID_NAME,
                    'pagination_state' => [
                        'state'   => [
                            '_pager' => [
                                '_page' => 2,
                                '_per_page' => 5
                            ],
                            '_sort_by' => [
                                'name' => 'ASC'
                            ],
                            '_filter'  => []
                        ],
                        'current_ids' => self::$current_ids,
                        'total' => 14,
                        'next_id' => 11,
                        'previous_id' => 5
                    ]
                ],
                'expected' => 6
            ],
        ];
    }

    public function totalCountDataProvider()
    {
        return [
            'in current ids' => [
                'entityId' => 8,
                'source' => [
                    'grid_name' => self::GRID_NAME,
                    'pagination_state' => [
                        'state'   => [
                            '_pager' => [
                                '_page' => 2,
                                '_per_page' => 5
                            ],
                            '_sort_by' => [
                                'name' => 'ASC'
                            ],
                            '_filter'  => []
                        ],
                        'current_ids' => self::$current_ids,
                        'total' => 14,
                        'next_id' => null,
                        'previous_id' => null
                    ]
                ],
                'expected' => 14,
            ],
            'not in storage' => [
                'entityId' => 40,
                'source' => [
                    'grid_name' => self::GRID_NAME,
                    'pagination_state' => [
                        'state'   => [
                            '_pager' => [
                                '_page' => 2,
                                '_per_page' => 5
                            ],
                            '_sort_by' => [
                                'name' => 'ASC'
                            ],
                            '_filter'  => []
                        ],
                        'current_ids' => self::$current_ids,
                        'total' => 14,
                        'next_id' => null,
                        'previous_id' => null
                    ]
                ],
                'expected' => null,
            ],
        ];
    }

    public function getCurrentNumberDataProvider()
    {
        return [
            'in current ids' => [
                'entityId' => 8,
                'source' => [
                    'grid_name' => self::GRID_NAME,
                    'pagination_state' => [
                        'state'   => [
                            '_pager' => [
                                '_page' => 2,
                                '_per_page' => 5
                            ],
                            '_sort_by' => [
                                'name' => 'ASC'
                            ],
                            '_filter'  => []
                        ],
                        'current_ids' => self::$current_ids,
                        'total' => 14,
                        'next_id' => null,
                        'previous_id' => null
                    ]
                ],
                'expected' => 8,
            ],
            'not in storage' => [
                'entityId' => 40,
                'source' => [
                    'grid_name' => self::GRID_NAME,
                    'pagination_state' => [
                        'state'   => [
                            '_pager' => [
                                '_page' => 2,
                                '_per_page' => 5
                            ],
                            '_sort_by' => [
                                'name' => 'ASC'
                            ],
                            '_filter'  => []
                        ],
                        'current_ids' => self::$current_ids,
                        'total' => 14,
                        'next_id' => null,
                        'previous_id' => null
                    ]
                ],
                'expected' => null,
            ],
        ];
    }

    /**
     * @param $entityId
     * @param $source
     * @param $expected
     * @param null $rebuild
     *
     * @dataProvider totalCountDataProvider
     */
    public function testGetTotalCount($entityId, $source, $expected, $rebuild = null)
    {
        $this->assertPrepareCurrentState($this->entity, $entityId, $source, $rebuild);
        $result = $this->storage->getTotalCount($this->entity);

        $this->assertEquals($expected, $result);

    }

    /**
     * @param $entityId
     * @param $source
     * @param $expected
     * @param $rebuild
     *
     * @dataProvider getCurrentNumberDataProvider
     */
    public function testGetCurrentNumber($entityId, $source, $expected, $rebuild = null)
    {
        $this->assertPrepareCurrentState($this->entity, $entityId, $source, $rebuild);
        $result = $this->storage->getCurrentNumber($this->entity);

        $this->assertEquals($expected, $result);
    }

    /**
     * @param $entityId
     * @param $source
     * @param $expected
     * @param $rebuild
     *
     * @dataProvider getPreviousDataProvider
     */
    public function testGetPreviousIdentifier($entityId, $source, $expected, $rebuild = null)
    {
        $this->assertPrepareCurrentState($this->entity, $entityId, $source, $rebuild);
        $result = $this->storage->getPreviousIdentifier($this->entity);

        $this->assertEquals($expected, $result);
    }


    /**
     * @param $entityId
     * @param $source
     * @param $expected
     * @param $rebuild
     *
     * @dataProvider getNextDataProvider
     */
    public function testGetNextIdentifier($entityId, $source, $expected, $rebuild = null)
    {
        $this->assertPrepareCurrentState($this->entity, $entityId, $source, $rebuild);
        $result = $this->storage->getNextIdentifier($this->entity);

        $this->assertSame($expected, $result);
    }

    /**
     * @param $entity
     * @param $entityId
     * @param $source
     * @param $rebuild
     */
    protected function assertPrepareCurrentState($entity, $entityId, $source, $rebuild)
    {
        $this->setStorage($source);

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->will($this->returnValue($entityId));

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifierFieldName')
            ->with($entity)
            ->will($this->returnValue(self::FIELD_NAME));

        if (null !== $rebuild) {
            $dataGrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');

            $source['pagination_state']['state']['_pager']['_page'] = $rebuild['page'];
            $source['pagination_state']['current_ids']     = $rebuild['current_ids'];

            $this->datagridManager->expects($this->once())
                ->method('getDataGrid')
                ->with($source[EntityPaginationStorage::GRID_NAME], $source['pagination_state']['state'])
                ->will($this->returnValue($dataGrid));

            $data = [];
            foreach ($rebuild['current_ids'] as $id) {
                $data[] = [self::FIELD_NAME => $id];
            }

            $dataGrid->expects($this->once())
                ->method('getData')
                ->will($this->returnValue(ResultsObject::create(['data' => $data])));

            $dataGrid->expects($this->once())
                ->method('getParameters')
                ->will($this->returnValue(new ParameterBag($source['pagination_state']['state'])));
        } else {
            $this->datagridManager->expects($this->never())
                ->method('getDataGrid');
        }
    }

    protected function setStorage($source)
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set(EntityPaginationStorage::STORAGE_NAME, [self::ENTITY_NAME => $source]);
        $request = new Request();
        $request->setSession($session);
        $this->storage->setRequest($request);
    }
}
