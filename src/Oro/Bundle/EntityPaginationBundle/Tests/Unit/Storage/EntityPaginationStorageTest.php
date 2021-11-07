<?php

namespace Oro\Bundle\EntityPaginationBundle\Tests\Unit\Storage;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityPaginationBundle\Manager\EntityPaginationManager;
use Oro\Bundle\EntityPaginationBundle\Storage\EntityPaginationStorage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class EntityPaginationStorageTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY_NAME = 'stdClass';
    private const HASH = '9b59e3bbc14e88a044c112a5b5e914a4';

    public static $entityIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var EntityPaginationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $paginationManager;

    /** @var RequestStack */
    private $requestStack;

    /** @var \stdClass */
    private $entity;

    /** @var EntityPaginationStorage */
    private $storage;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->paginationManager = $this->createMock(EntityPaginationManager::class);
        $this->requestStack = new RequestStack();
        $this->entity = new \stdClass();

        $this->storage = new EntityPaginationStorage(
            $this->doctrineHelper,
            $this->paginationManager,
            $this->requestStack
        );
    }

    /**
     * @dataProvider setDataDataProvider
     */
    public function testSetData(bool $enabled, bool $request, array $source, bool $expected)
    {
        $this->setEnabled($enabled);

        if (true === $request) {
            $this->setRequest();
        }

        $result = $this->storage->setData(self::ENTITY_NAME, $source['hash'], $source['entity_ids'], 'VIEW');
        $this->assertSame($expected, $result);
    }

    public function setDataDataProvider(): array
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
     * @dataProvider hasDataDataProvider
     */
    public function testHasData(bool $enabled, bool $request, string $entityName, string $hash, bool $expected)
    {
        $this->setEnabled($enabled);

        if (true === $request) {
            $this->setRequest();
        }

        $this->storage->setData(self::ENTITY_NAME, self::HASH, [], 'VIEW');
        $result = $this->storage->hasData($entityName, $hash, 'VIEW');

        $this->assertSame($expected, $result);
    }

    public function hasDataDataProvider(): array
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
     * @dataProvider getCurrentPositionDataProvider
     */
    public function testGetCurrentPosition(array $source, int|bool $expected)
    {
        $this->setStorage($source['storage_data']);

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($this->entity)
            ->willReturn($source['entity_id']);

        $result = $this->storage->getCurrentPosition($this->entity, $source['scope']);

        $this->assertSame($expected, $result);
    }

    public function getCurrentPositionDataProvider(): array
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
     * @dataProvider isEntityInStorageDataProvider
     */
    public function testIsEntityInStorage(array $source, bool $expected)
    {
        $this->setStorage($source['storage_data']);

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($this->entity)
            ->willReturn($source['entity_id']);

        $result = $this->storage->isEntityInStorage($this->entity, $source['scope']);

        $this->assertSame($expected, $result);
    }

    public function isEntityInStorageDataProvider(): array
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
     * @dataProvider unsetIdentifierDataProvider
     */
    public function testUnsetIdentifier(array $source, array $expected)
    {
        $this->setStorage($source['storage_data']);
        $this->storage->unsetIdentifier($source['identifier'], $this->entity, $source['scope']);
        $result = $this->storage->getEntityIds($this->entity, $source['scope']);

        $this->assertSame($expected, $result);
    }

    public function unsetIdentifierDataProvider(): array
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
     * @dataProvider clearDataDataProvider
     */
    public function testClearData(bool $enabled, bool $request, array $source, array $expected)
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

    public function clearDataDataProvider(): array
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
     * @dataProvider isInfoMessageShownDataProvider
     */
    public function testIsInfoMessageShown(?bool $expected, bool $enabled = false, array $storage = null)
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

    public function isInfoMessageShownDataProvider(): array
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
     * @dataProvider setInfoMessageShownDataProvider
     */
    public function testSetInfoMessageShown(bool $expected, bool $enabled = false, ?bool $shown = false)
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

    public function setInfoMessageShownDataProvider(): array
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

    private function setStorage(array $storageData): void
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

    private function setEnabled(bool $isEnabled): void
    {
        $this->paginationManager->expects($this->any())
            ->method('isEnabled')
            ->willReturn($isEnabled);
    }

    private function setRequest(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);
        $this->requestStack->push($request);
    }
}
