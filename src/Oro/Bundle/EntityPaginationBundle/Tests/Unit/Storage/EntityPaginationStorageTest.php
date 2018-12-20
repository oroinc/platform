<?php

namespace Oro\Bundle\EntityPaginationBundle\Tests\Unit\Storage;

use Oro\Bundle\EntityPaginationBundle\Manager\EntityPaginationManager;
use Oro\Bundle\EntityPaginationBundle\Storage\EntityPaginationStorage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class EntityPaginationStorageTest extends \PHPUnit\Framework\TestCase
{
    const ENTITY_NAME = 'stdClass';
    const FIELD_NAME  = 'id';
    const HASH        = '9b59e3bbc14e88a044c112a5b5e914a4';

    public static $entityIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $paginationManager;

    /** @var EntityPaginationStorage */
    protected $storage;

    /** @var \stdClass */
    protected $entity;

    /** @var RequestStack */
    private $requestStack;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paginationManager = $this->getMockBuilder(
            'Oro\Bundle\EntityPaginationBundle\Manager\EntityPaginationManager'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestStack = new RequestStack();
        $this->storage = new EntityPaginationStorage(
            $this->doctrineHelper,
            $this->paginationManager,
            $this->requestStack
        );
        $this->entity = new \stdClass();
    }

    /**
     * @param bool $enabled
     * @param bool $request
     * @param array $source
     * @param bool $expected
     *
     * @dataProvider setDataDataProvider
     */
    public function testSetData($enabled, $request, array $source, $expected)
    {
        $this->setEnabled($enabled);

        if (true === $request) {
            $this->setRequest();
        }

        $result = $this->storage->setData(self::ENTITY_NAME, $source['hash'], $source['entity_ids'], 'VIEW');
        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function setDataDataProvider()
    {
        return [
            'is set request' => [
                'enabled'  => true,
                'request'  => true,
                'source'   => [
                    'hash'       => self::HASH,
                    'entity_ids' => self::$entityIds
                ],
                'expected' => true
            ],
            'is not set request' => [
                'enabled'  => true,
                'request'  => false,
                'source'   => [
                    'hash'       => self::HASH,
                    'entity_ids' => self::$entityIds
                ],
                'expected' => false
            ],
            'not enabled' => [
                'enabled'  => false,
                'request'  => true,
                'source'   => [
                    'hash'       => self::HASH,
                    'entity_ids' => self::$entityIds
                ],
                'expected' => false
            ],
        ];
    }

    /**
     * @param bool $enabled
     * @param bool $request
     * @param string $entityName
     * @param string $hash
     * @param bool $expected
     *
     * @dataProvider hasDataDataProvider
     */
    public function testHasData($enabled, $request, $entityName, $hash, $expected)
    {
        $this->setEnabled($enabled);

        if (true === $request) {
            $this->setRequest();
        }

        $this->storage->setData(self::ENTITY_NAME, self::HASH, [], 'VIEW');
        $result = $this->storage->hasData($entityName, $hash, 'VIEW');

        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function hasDataDataProvider()
    {
        return [
            'not enabled' => [
                'enabled' => false,
                'request' => true,
                'entityName' => self::ENTITY_NAME,
                'hash' => self::HASH,
                'expected' => false,
            ],
            'no request' => [
                'enabled' => true,
                'request' => false,
                'entityName' => self::ENTITY_NAME,
                'hash' => self::HASH,
                'expected' => false,
            ],
            'no entity' => [
                'enabled' => true,
                'request' => true,
                'entityName' => 'not existing entity',
                'hash' => 'not existing hash',
                'expected' => false,
            ],
            'no hash' => [
                'enabled' => true,
                'request' => true,
                'entityName' => self::ENTITY_NAME,
                'hash' => 'not existing hash',
                'expected' => false,
            ],
            'data exists' => [
                'enabled' => true,
                'request' => true,
                'entityName' => self::ENTITY_NAME,
                'hash' => self::HASH,
                'expected' => true,
            ],
        ];
    }

    /**
     * @param array $source
     * @param int $expected
     *
     * @dataProvider getCurrentPositionDataProvider
     */
    public function testGetCurrentPosition(array $source, $expected)
    {
        $this->setStorage($source['storage_data']);

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($this->entity)
            ->will($this->returnValue($source['entity_id']));

        $result = $this->storage->getCurrentPosition($this->entity, $source['scope']);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function getCurrentPositionDataProvider()
    {
        return [
            'valid case view' => [
                'source'   => [
                    'entity_id'    => 5,
                    'scope'        => EntityPaginationManager::VIEW_SCOPE,
                    'storage_data' => [
                        EntityPaginationManager::VIEW_SCOPE => [
                            'hash'       => self::HASH,
                            'entity_ids' => self::$entityIds
                        ]
                    ]
                ],
                'expected' => 4
            ],
            'valid case edit' => [
                'source'   => [
                    'entity_id'    => 5,
                    'scope'        => EntityPaginationManager::EDIT_SCOPE,
                    'storage_data' => [
                        EntityPaginationManager::EDIT_SCOPE => [
                            'hash'       => self::HASH,
                            'entity_ids' => self::$entityIds
                        ]
                    ]
                ],
                'expected' => 4
            ],
            'not in ids' => [
                'source'   => [
                    'entity_id'    => 100,
                    'scope'        => EntityPaginationManager::VIEW_SCOPE,
                    'storage_data' => [
                        EntityPaginationManager::VIEW_SCOPE => [
                            'hash'       => self::HASH,
                            'entity_ids' => self::$entityIds
                        ]
                    ]
                ],
                'expected' => false
            ],
        ];
    }

    /**
     * @param array $source
     * @param boolean $expected
     *
     * @dataProvider isEntityInStorageDataProvider
     */
    public function testIsEntityInStorage($source, $expected)
    {
        $this->setStorage($source['storage_data']);

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($this->entity)
            ->will($this->returnValue($source['entity_id']));

        $result = $this->storage->isEntityInStorage($this->entity, $source['scope']);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function isEntityInStorageDataProvider()
    {
        return [
            'valid case view' => [
                'source'   => [
                    'entity_id'    => 5,
                    'scope'        => EntityPaginationManager::VIEW_SCOPE,
                    'storage_data' => [
                        EntityPaginationManager::VIEW_SCOPE => [
                            'hash'       => self::HASH,
                            'entity_ids' => self::$entityIds
                        ]
                    ]
                ],
                'expected' => true
            ],
            'valid case edit' => [
                'source'   => [
                    'entity_id'    => 5,
                    'scope'        => EntityPaginationManager::EDIT_SCOPE,
                    'storage_data' => [
                        EntityPaginationManager::EDIT_SCOPE => [
                            'hash'       => self::HASH,
                            'entity_ids' => self::$entityIds
                        ]
                    ]
                ],
                'expected' => true
            ],
            'empty ids' => [
                'source'   => [
                    'entity_id'    => 5,
                    'scope'        => EntityPaginationManager::VIEW_SCOPE,
                    'storage_data' => [
                        EntityPaginationManager::VIEW_SCOPE => [
                            'hash'       => self::HASH,
                            'entity_ids' => []
                        ]
                    ]
                ],
                'expected' => false
            ],
            'not in ids' => [
                'source'   => [
                    'entity_id'    => 100,
                    'scope'        => EntityPaginationManager::VIEW_SCOPE,
                    'storage_data' => [
                        EntityPaginationManager::VIEW_SCOPE => [
                            'hash'       => self::HASH,
                            'entity_ids' => self::$entityIds
                        ]
                    ]
                ],
                'expected' => false
            ],
        ];
    }

    /**
     * @param array $source
     * @param array $expected
     *
     * @dataProvider unsetIdentifierDataProvider
     */
    public function testUnsetIdentifier(array $source, array $expected)
    {
        $this->setStorage($source['storage_data']);
        $this->storage->unsetIdentifier($source['identifier'], $this->entity, $source['scope']);
        $result = $this->storage->getEntityIds($this->entity, $source['scope']);

        $this->assertSame($expected, $result);
    }

    /**
     * @return array
     */
    public function unsetIdentifierDataProvider()
    {
        return [
            'valid case view' => [
                'source'   => [
                    'identifier'   => 3,
                    'scope'        => EntityPaginationManager::VIEW_SCOPE,
                    'storage_data' => [
                        EntityPaginationManager::VIEW_SCOPE => [
                            'hash'       => self::HASH,
                            'entity_ids' => [1, 2, 3, 4, 5]
                        ]
                    ]
                ],
                'expected' => [1, 2, 4, 5]
            ],
        ];
    }

    /**
     * @param boolean $enabled
     * @param boolean $request
     * @param array $source
     * @param boolean $expected
     *
     * @dataProvider clearDataDataProvider
     */
    public function testClearData($enabled, $request, $source, $expected)
    {
        $this->setEnabled($enabled);

        if (true === $request) {
            $this->setRequest();
        }

        $this->setStorage($source['storage_data']);
        $result = $this->storage->clearData(self::ENTITY_NAME, $source['scope']);
        $viewScopesIds = $this->storage->getEntityIds($this->entity, EntityPaginationManager::VIEW_SCOPE);
        $editScopesIds = $this->storage->getEntityIds($this->entity, EntityPaginationManager::EDIT_SCOPE);

        $this->assertEquals($expected['result'], $result);
        if ($expected['result'] === true) {
            $this->assertEquals($expected['view_scope_ids'], $viewScopesIds);
            $this->assertEquals($expected['edit_scope_ids'], $editScopesIds);
        }
    }

    public function clearDataDataProvider()
    {
        return [
            'not valid environment' => [
                'enabled' => false,
                'request' => true,
                'source'   => [
                    'identifier'   => 3,
                    'scope'        => EntityPaginationManager::VIEW_SCOPE,
                    'storage_data' => [
                        EntityPaginationManager::VIEW_SCOPE => [
                            'hash'       => self::HASH,
                            'entity_ids' => [1, 2, 3, 4, 5]
                        ],
                        EntityPaginationManager::EDIT_SCOPE => [
                            'hash'       => self::HASH,
                            'entity_ids' => [1, 2, 3]
                        ]
                    ]
                ],
                'expected' => [
                    'result' => false,
                    'view_scope_ids' => [],
                    'edit_scope_ids' => [1, 2, 3],
                ]
            ],
            'clear view scope' => [
                'enabled' => true,
                'request' => true,
                'source'   => [
                    'identifier'   => 3,
                    'scope'        => EntityPaginationManager::VIEW_SCOPE,
                    'storage_data' => [
                        EntityPaginationManager::VIEW_SCOPE => [
                            'hash'       => self::HASH,
                            'entity_ids' => [1, 2, 3, 4, 5]
                        ],
                        EntityPaginationManager::EDIT_SCOPE => [
                            'hash'       => self::HASH,
                            'entity_ids' => [1, 2, 3]
                        ]
                    ]
                ],
                'expected' => [
                    'result' => true,
                    'view_scope_ids' => [],
                    'edit_scope_ids' => [1, 2, 3],
                ]
            ],
            'clear edit scope' => [
                'enabled' => true,
                'request' => true,
                'source'   => [
                    'identifier'   => 3,
                    'scope'        => EntityPaginationManager::EDIT_SCOPE,
                    'storage_data' => [
                        EntityPaginationManager::VIEW_SCOPE => [
                            'hash'       => self::HASH,
                            'entity_ids' => [1, 2, 3, 4, 5]
                        ],
                        EntityPaginationManager::EDIT_SCOPE => [
                            'hash'       => self::HASH,
                            'entity_ids' => [1, 2, 3]
                        ]
                    ]
                ],
                'expected' => [
                    'result' => true,
                    'view_scope_ids' => [1, 2, 3, 4, 5],
                    'edit_scope_ids' => [],
                ]
            ],
            'clear both scopes' => [
                'enabled' => true,
                'request' => true,
                'source'   => [
                    'identifier'   => 3,
                    'scope'        => null,
                    'storage_data' => [
                        EntityPaginationManager::VIEW_SCOPE => [
                            'hash'       => self::HASH,
                            'entity_ids' => [1, 2, 3, 4, 5]
                        ],
                        EntityPaginationManager::EDIT_SCOPE => [
                            'hash'       => self::HASH,
                            'entity_ids' => [1, 2, 3]
                        ]
                    ]
                ],
                'expected' => [
                    'result' => true,
                    'view_scope_ids' => [],
                    'edit_scope_ids' => [],
                ]
            ]
        ];
    }

    /**
     * @param bool $expected
     * @param bool $enabled
     * @param array $storage
     * @dataProvider isInfoMessageShownDataProvider
     */
    public function testIsInfoMessageShown($expected, $enabled = false, array $storage = null)
    {
        $this->setEnabled($enabled);

        if (null !== $storage) {
            $this->setStorage($storage);
        }

        $this->assertSame(
            $expected,
            $this->storage->isInfoMessageShown(self::ENTITY_NAME, EntityPaginationManager::VIEW_SCOPE)
        );
    }

    /**
     * @return array
     */
    public function isInfoMessageShownDataProvider()
    {
        return [
            'invalid environment' => [
                'expected' => null,
            ],
            'message not shown' => [
                'expected' => false,
                'enabled' => true,
                'storage' => [],
            ],
            'message shown' => [
                'expected' => true,
                'enabled' => true,
                'storage' => [
                    EntityPaginationManager::VIEW_SCOPE => [
                        EntityPaginationStorage::INFO_MESSAGE => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param bool $expected
     * @param bool $enabled
     * @param bool $shown
     * @dataProvider setInfoMessageShownDataProvider
     */
    public function testSetInfoMessageShown($expected, $enabled = false, $shown = false)
    {
        $this->setEnabled($enabled);

        if ($enabled) {
            $this->setRequest();
        }

        $this->assertSame(
            $expected,
            $this->storage->setInfoMessageShown(self::ENTITY_NAME, EntityPaginationManager::VIEW_SCOPE, $shown)
        );
        $this->assertSame(
            $shown,
            $this->storage->isInfoMessageShown(self::ENTITY_NAME, EntityPaginationManager::VIEW_SCOPE)
        );
    }

    public function setInfoMessageShownDataProvider()
    {
        return [
            'invalid environment' => [
                'expected' => false,
                'enabled' => false,
                'shown' => null,
            ],
            'message not shown' => [
                'expected' => true,
                'enabled' => true,
                'shown' => false,
            ],
            'message shown' => [
                'expected' => true,
                'enabled' => true,
                'shown' => true,
            ],
        ];
    }

    /**
     * @param array $storageData
     */
    protected function setStorage(array $storageData)
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set(
            EntityPaginationStorage::STORAGE_NAME,
            [self::ENTITY_NAME => $storageData]
        );
        $request = new Request();
        $request->setSession($session);
        $this->requestStack->push($request);
    }

    /**
     * @param boolean $isEnabled
     */
    protected function setEnabled($isEnabled)
    {
        $this->paginationManager->expects($this->any())
            ->method('isEnabled')
            ->will($this->returnValue($isEnabled));
    }

    protected function setRequest()
    {
        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);
        $this->requestStack->push($request);
    }
}
