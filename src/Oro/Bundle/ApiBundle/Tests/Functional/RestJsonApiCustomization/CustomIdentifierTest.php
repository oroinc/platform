<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiCustomization;

use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestCustomIdentifier as TestEntity;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestOrder;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestOrderLineItem;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Component\ChainProcessor\Exception\ExecutionFailedException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class CustomIdentifierTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/custom_identifier.yml'
        ]);
    }

    private function getEntityId(string $key): string
    {
        return $key;
    }

    public function testGetList()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->cget(['entity' => $entityType]);

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => $entityType,
                        'id'            => $this->getEntityId('item 1'),
                        'attributes'    => [
                            'databaseId' => '@test_custom_id1->id',
                            'name'       => 'Item 1'
                        ],
                        'relationships' => [
                            'parent'   => [
                                'data' => null
                            ],
                            'children' => [
                                'data' => [
                                    ['type' => $entityType, 'id' => $this->getEntityId('item 3')]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'          => $entityType,
                        'id'            => $this->getEntityId('item 2'),
                        'attributes'    => [
                            'databaseId' => '@test_custom_id2->id',
                            'name'       => 'Item 2'
                        ],
                        'relationships' => [
                            'parent'   => [
                                'data' => ['type' => $entityType, 'id' => $this->getEntityId('item 3')]
                            ],
                            'children' => [
                                'data' => []
                            ]
                        ]
                    ],
                    [
                        'type'          => $entityType,
                        'id'            => $this->getEntityId('item 3'),
                        'attributes'    => [
                            'databaseId' => '@test_custom_id3->id',
                            'name'       => 'Item 3'
                        ],
                        'relationships' => [
                            'parent'   => [
                                'data' => ['type' => $entityType, 'id' => $this->getEntityId('item 1')]
                            ],
                            'children' => [
                                'data' => [
                                    ['type' => $entityType, 'id' => $this->getEntityId('item 2')]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithTitles()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->cget(
            ['entity' => $entityType],
            ['meta' => 'title', 'fields[' . $entityType . ']' => 'id']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id'   => $this->getEntityId('item 1'),
                        'meta' => [
                            'title' => 'item 1 Item 1'
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id'   => $this->getEntityId('item 2'),
                        'meta' => [
                            'title' => 'item 2 Item 2'
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id'   => $this->getEntityId('item 3'),
                        'meta' => [
                            'title' => 'item 3 Item 3'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithTitlesAndIncludedDataRequested()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->cget(
            ['entity' => $entityType],
            [
                'meta'                        => 'title',
                'fields[' . $entityType . ']' => 'id,parent',
                'include'                     => 'parent',
                'filter[id]'                  => $this->getEntityId('item 3')
            ]
        );

        $this->assertResponseContains(
            [
                'data'     => [
                    [
                        'type' => $entityType,
                        'id'   => $this->getEntityId('item 3'),
                        'meta' => [
                            'title' => 'item 3 Item 3'
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => $entityType,
                        'id'   => $this->getEntityId('item 1'),
                        'meta' => [
                            'title' => 'item 1 Item 1'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListSortById()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->cget(
            ['entity' => $entityType],
            ['sort' => '-id', 'fields[' . $entityType . ']' => 'id']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id'   => $this->getEntityId('item 3')
                    ],
                    [
                        'type' => $entityType,
                        'id'   => $this->getEntityId('item 2')
                    ],
                    [
                        'type' => $entityType,
                        'id'   => $this->getEntityId('item 1')
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilterById()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->cget(
            ['entity' => $entityType],
            ['filter[id]' => $this->getEntityId('item 3')]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => $entityType,
                        'id'            => $this->getEntityId('item 3'),
                        'attributes'    => [
                            'databaseId' => '@test_custom_id3->id',
                            'name'       => 'Item 3'
                        ],
                        'relationships' => [
                            'parent'   => [
                                'data' => ['type' => $entityType, 'id' => $this->getEntityId('item 1')]
                            ],
                            'children' => [
                                'data' => [
                                    ['type' => $entityType, 'id' => $this->getEntityId('item 2')]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilterBySeveralIds()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->cget(
            ['entity' => $entityType],
            ['filter[id]' => $this->getEntityId('item 1') . ',' . $this->getEntityId('item 3')]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => $entityType,
                        'id'            => $this->getEntityId('item 1'),
                        'attributes'    => [
                            'databaseId' => '@test_custom_id1->id',
                            'name'       => 'Item 1'
                        ],
                        'relationships' => [
                            'parent'   => [
                                'data' => null
                            ],
                            'children' => [
                                'data' => [
                                    ['type' => $entityType, 'id' => $this->getEntityId('item 3')]
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'          => $entityType,
                        'id'            => $this->getEntityId('item 3'),
                        'attributes'    => [
                            'databaseId' => '@test_custom_id3->id',
                            'name'       => 'Item 3'
                        ],
                        'relationships' => [
                            'parent'   => [
                                'data' => ['type' => $entityType, 'id' => $this->getEntityId('item 1')]
                            ],
                            'children' => [
                                'data' => [
                                    ['type' => $entityType, 'id' => $this->getEntityId('item 2')]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilterByDatabasePrimaryKey()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->cget(
            ['entity' => $entityType],
            ['filter[databaseId]' => '@test_custom_id3->id']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => $entityType,
                        'id'            => $this->getEntityId('item 3'),
                        'attributes'    => [
                            'databaseId' => '@test_custom_id3->id',
                            'name'       => 'Item 3'
                        ],
                        'relationships' => [
                            'parent'   => [
                                'data' => ['type' => $entityType, 'id' => $this->getEntityId('item 1')]
                            ],
                            'children' => [
                                'data' => [
                                    ['type' => $entityType, 'id' => $this->getEntityId('item 2')]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilterByField()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->cget(
            ['entity' => $entityType],
            ['filter[name]' => 'Item 3']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => $entityType,
                        'id'            => $this->getEntityId('item 3'),
                        'attributes'    => [
                            'databaseId' => '@test_custom_id3->id',
                            'name'       => 'Item 3'
                        ],
                        'relationships' => [
                            'parent'   => [
                                'data' => ['type' => $entityType, 'id' => $this->getEntityId('item 1')]
                            ],
                            'children' => [
                                'data' => [
                                    ['type' => $entityType, 'id' => $this->getEntityId('item 2')]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilterByChildId()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->cget(
            ['entity' => $entityType],
            ['filter[children]' => $this->getEntityId('item 3')]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => $entityType,
                        'id'            => $this->getEntityId('item 1'),
                        'attributes'    => [
                            'databaseId' => '@test_custom_id1->id',
                            'name'       => 'Item 1'
                        ],
                        'relationships' => [
                            'parent'   => [
                                'data' => null
                            ],
                            'children' => [
                                'data' => [
                                    ['type' => $entityType, 'id' => $this->getEntityId('item 3')]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilterByAssociationThatNameEqualsToDatabaseKeyword()
    {
        $this->appendEntityConfig(
            TestOrder::class,
            [
                'identifier_field_names' => ['poNumber']
            ]
        );

        $entityType = $this->getEntityType(TestOrderLineItem::class);
        $lineItemId = $this->getReference('order_line_item1')->getId();

        $response = $this->cget(
            ['entity' => $entityType],
            ['filter[order]' => 'PO1']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => $entityType, 'id' => (string)$lineItemId]
                ]
            ],
            $response
        );
    }

    public function testGet()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->get(['entity' => $entityType, 'id' => $this->getEntityId('item 3')]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => $entityType,
                    'id'            => $this->getEntityId('item 3'),
                    'attributes'    => [
                        'databaseId' => '@test_custom_id3->id',
                        'name'       => 'Item 3'
                    ],
                    'relationships' => [
                        'parent'   => [
                            'data' => ['type' => $entityType, 'id' => $this->getEntityId('item 1')]
                        ],
                        'children' => [
                            'data' => [
                                ['type' => $entityType, 'id' => $this->getEntityId('item 2')]
                            ]
                        ]
                    ]
                ],
            ],
            $response
        );
    }

    public function testExcludeDatabasePrimaryKey()
    {
        $this->appendEntityConfig(
            TestEntity::class,
            [
                'fields' => [
                    'databaseId' => [
                        'exclude' => true
                    ]
                ]
            ]
        );

        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->get(['entity' => $entityType, 'id' => $this->getEntityId('item 3')]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => $entityType,
                    'id'         => $this->getEntityId('item 3'),
                    'attributes' => [
                        'name' => 'Item 3'
                    ],
                ],
            ],
            $response
        );
        $content = self::jsonToArray($response->getContent());
        self::assertFalse(isset($content['data']['attributes']['databaseId']));
    }

    public function testTryToExcludeIdentifierField()
    {
        $this->appendEntityConfig(
            TestEntity::class,
            [
                'fields' => [
                    'key' => [
                        'exclude' => true
                    ]
                ]
            ]
        );

        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->get(
            ['entity' => $entityType, 'id' => $this->getEntityId('item 3')],
            [],
            [],
            false
        );

        $this->assertResponseValidationError(
            ['title' => 'runtime exception'],
            $response,
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
        $content = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('detail', $content['errors'][0]);

        /** @var ConfigProvider $configProvider */
        $configProvider = self::getContainer()->get('oro_api.config_provider');
        try {
            $configProvider->getConfig(
                TestEntity::class,
                Version::LATEST,
                $this->getRequestType(),
                [new EntityDefinitionConfigExtra(ApiAction::GET)]
            );
            self::fail('Expected ExecutionFailedException');
        } catch (ExecutionFailedException $e) {
            self::assertEquals(
                sprintf(
                    'Processor failed: "oro_api.get_config.complete_definition".'
                    . ' Reason: Processor failed: "oro_api.get_config.complete_definition".'
                    . ' Reason: The identifier field "key" for "%s" entity must not be excluded.',
                    TestEntity::class
                ),
                $e->getMessage()
            );
        }
    }

    public function testGetWithTitles()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->get(
            ['entity' => $entityType, 'id' => $this->getEntityId('item 3')],
            ['meta' => 'title']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'id'   => $this->getEntityId('item 3'),
                    'meta' => [
                        'title' => 'item 3 Item 3'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetWithTitlesAndIncludedDataRequested()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->get(
            ['entity' => $entityType, 'id' => $this->getEntityId('item 3')],
            ['meta' => 'title', 'fields[' . $entityType . ']' => 'id,parent', 'include' => 'parent']
        );

        $this->assertResponseContains(
            [
                'data'     => [
                    'type' => $entityType,
                    'id'   => $this->getEntityId('item 3'),
                    'meta' => [
                        'title' => 'item 3 Item 3'
                    ]
                ],
                'included' => [
                    [
                        'type' => $entityType,
                        'id'   => $this->getEntityId('item 1'),
                        'meta' => [
                            'title' => 'item 1 Item 1'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testCreate()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $data = [
            'data' => [
                'type'          => $entityType,
                'id'            => $this->getEntityId('new item'),
                'attributes'    => [
                    'name' => 'New Item'
                ],
                'relationships' => [
                    'parent'   => [
                        'data' => ['type' => $entityType, 'id' => $this->getEntityId('item 1')]
                    ],
                    'children' => [
                        'data' => [
                            ['type' => $entityType, 'id' => $this->getEntityId('item 1')]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => $entityType,
                    'id'            => $this->getEntityId('new item'),
                    'attributes'    => [
                        'name' => 'New Item'
                    ],
                    'relationships' => [
                        'parent'   => [
                            'data' => ['type' => $entityType, 'id' => $this->getEntityId('item 1')]
                        ],
                        'children' => [
                            'data' => [
                                ['type' => $entityType, 'id' => $this->getEntityId('item 1')]
                            ]
                        ]
                    ]
                ],
            ],
            $response
        );

        $this->getEntityManager()->clear();
        /** @var TestEntity $createdEntity */
        $createdEntity = $this->getEntityManager()
            ->getRepository(TestEntity::class)
            ->findOneBy(['key' => 'new item']);
        self::assertNotNull($createdEntity);

        $result = self::jsonToArray($response->getContent());
        self::assertSame($createdEntity->id, $result['data']['attributes']['databaseId']);

        self::assertSame('New Item', $createdEntity->name);
        self::assertSame($this->getReference('test_custom_id1')->id, $createdEntity->getParent()->id);
        self::assertCount(1, $createdEntity->getChildren());
        self::assertSame($this->getReference('test_custom_id1')->id, $createdEntity->getChildren()->first()->id);
    }

    public function testTryToCreateWithoutName()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => $this->getEntityId('new item'),
                    'relationships' => [
                        'parent' => [
                            'data' => ['type' => $entityType, 'id' => $this->getEntityId('item 1')]
                        ],
                        'children' => [
                            'data' => [
                                ['type' => $entityType, 'id' => $this->getEntityId('item 1')]
                            ]
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/name']
            ],
            $response
        );
    }

    public function testUpdate()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $data = [
            'data' => [
                'type'          => $entityType,
                'id'            => $this->getEntityId('item 1'),
                'attributes'    => [
                    'databaseId' => '@test_custom_id1->id',
                    'name'       => 'Updated Name'
                ],
                'relationships' => [
                    'parent'   => [
                        'data' => ['type' => $entityType, 'id' => $this->getEntityId('item 2')]
                    ],
                    'children' => [
                        'data' => [
                            ['type' => $entityType, 'id' => $this->getEntityId('item 2')]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->patch(['entity' => $entityType, 'id' => $this->getEntityId('item 1')], $data);

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => $entityType,
                    'id'            => $this->getEntityId('item 1'),
                    'attributes'    => [
                        'name' => 'Updated Name'
                    ],
                    'relationships' => [
                        'parent'   => [
                            'data' => ['type' => $entityType, 'id' => $this->getEntityId('item 2')]
                        ],
                        'children' => [
                            'data' => [
                                ['type' => $entityType, 'id' => $this->getEntityId('item 2')]
                            ]
                        ]
                    ]
                ],
            ],
            $response
        );

        $this->getEntityManager()->clear();
        /** @var TestEntity $updatedEntity */
        $updatedEntity = $this->getEntityManager()
            ->getRepository(TestEntity::class)
            ->findOneBy(['key' => 'item 1']);
        self::assertNotNull($updatedEntity);
        self::assertSame('Updated Name', $updatedEntity->name);
        self::assertSame($this->getReference('test_custom_id2')->id, $updatedEntity->getParent()->id);
        self::assertCount(1, $updatedEntity->getChildren());
        self::assertSame($this->getReference('test_custom_id2')->id, $updatedEntity->getChildren()->first()->id);
    }

    public function testTryToUpdateNameToNull()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->patch(
            ['entity' => $entityType, 'id' => $this->getEntityId('item 1')],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => $this->getEntityId('item 1'),
                    'attributes' => [
                        'databaseId' => '@test_custom_id1->id',
                        'name' => null
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/name']
            ],
            $response
        );
    }

    public function testDelete()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $this->delete(['entity' => $entityType, 'id' => $this->getEntityId('item 2')]);

        $this->getEntityManager()->clear();
        $deletedEntity = $this->getEntityManager()
            ->getRepository(TestEntity::class)
            ->findOneBy(['key' => 'item 2']);
        self::assertNull($deletedEntity);
    }

    public function testGetSubresourceForToOneAssociation()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->getSubresource(
            ['entity' => $entityType, 'id' => $this->getEntityId('item 3'), 'association' => 'parent']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => $entityType,
                    'id'            => $this->getEntityId('item 1'),
                    'attributes'    => [
                        'databaseId' => '@test_custom_id1->id',
                        'name'       => 'Item 1'
                    ],
                    'relationships' => [
                        'parent'   => [
                            'data' => null
                        ],
                        'children' => [
                            'data' => [
                                ['type' => $entityType, 'id' => $this->getEntityId('item 3')]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceForToOneAssociationWithTitles()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->getSubresource(
            ['entity' => $entityType, 'id' => $this->getEntityId('item 3'), 'association' => 'parent'],
            ['meta' => 'title']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'id'   => $this->getEntityId('item 1'),
                    'meta' => [
                        'title' => 'item 1 Item 1',
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceForToManyAssociation()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->getSubresource(
            ['entity' => $entityType, 'id' => $this->getEntityId('item 3'), 'association' => 'children']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => $entityType,
                        'id'            => $this->getEntityId('item 2'),
                        'attributes'    => [
                            'databaseId' => '@test_custom_id2->id',
                            'name'       => 'Item 2'
                        ],
                        'relationships' => [
                            'parent'   => [
                                'data' => ['type' => $entityType, 'id' => $this->getEntityId('item 3')]
                            ],
                            'children' => [
                                'data' => []
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceForToManyAssociationWithTitles()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->getSubresource(
            ['entity' => $entityType, 'id' => $this->getEntityId('item 3'), 'association' => 'children'],
            ['meta' => 'title']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id'   => $this->getEntityId('item 2'),
                        'meta' => [
                            'title' => 'item 2 Item 2',
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForToOneAssociation()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->getRelationship(
            ['entity' => $entityType, 'id' => $this->getEntityId('item 3'), 'association' => 'parent']
        );

        $this->assertResponseContains(
            [
                'data' => ['type' => $entityType, 'id' => $this->getEntityId('item 1')]
            ],
            $response
        );
    }

    public function testGetRelationshipForToManyAssociation()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->getRelationship(
            ['entity' => $entityType, 'id' => $this->getEntityId('item 3'), 'association' => 'children']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => $entityType, 'id' => $this->getEntityId('item 2')]
                ]
            ],
            $response
        );
    }

    public function testUpdateRelationshipForToOneAssociation()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $this->patchRelationship(
            ['entity' => $entityType, 'id' => $this->getEntityId('item 3'), 'association' => 'parent'],
            [
                'data' => ['type' => $entityType, 'id' => $this->getEntityId('item 2')]
            ]
        );

        $this->getEntityManager()->clear();
        /** @var TestEntity $updatedEntity */
        $updatedEntity = $this->getEntityManager()
            ->getRepository(TestEntity::class)
            ->findOneBy(['key' => 'item 3']);
        self::assertNotNull($updatedEntity);
        self::assertSame($this->getReference('test_custom_id2')->id, $updatedEntity->getParent()->id);
    }

    public function testUpdateRelationshipForToManyAssociation()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $this->patchRelationship(
            ['entity' => $entityType, 'id' => $this->getEntityId('item 3'), 'association' => 'children'],
            [
                'data' => [
                    ['type' => $entityType, 'id' => $this->getEntityId('item 1')],
                    ['type' => $entityType, 'id' => $this->getEntityId('item 2')],
                ]
            ]
        );

        $this->getEntityManager()->clear();
        /** @var TestEntity $updatedEntity */
        $updatedEntity = $this->getEntityManager()
            ->getRepository(TestEntity::class)
            ->findOneBy(['key' => 'item 3']);
        self::assertNotNull($updatedEntity);
        self::assertCount(2, $updatedEntity->getChildren());
        $ids = [$updatedEntity->getChildren()->get(0)->id, $updatedEntity->getChildren()->get(1)->id];
        sort($ids);
        self::assertSame($this->getReference('test_custom_id1')->id, $ids[0]);
        self::assertSame($this->getReference('test_custom_id2')->id, $ids[1]);
    }

    public function testDeleteRelationshipForToManyAssociation()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $this->deleteRelationship(
            ['entity' => $entityType, 'id' => $this->getEntityId('item 3'), 'association' => 'children'],
            [
                'data' => [
                    ['type' => $entityType, 'id' => $this->getEntityId('item 2')]
                ]
            ]
        );

        $this->getEntityManager()->clear();
        /** @var TestEntity $updatedEntity */
        $updatedEntity = $this->getEntityManager()
            ->getRepository(TestEntity::class)
            ->findOneBy(['key' => 'item 3']);
        self::assertNotNull($updatedEntity);
        self::assertCount(0, $updatedEntity->getChildren());
    }

    public function testAddRelationshipForToManyAssociation()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $this->postRelationship(
            ['entity' => $entityType, 'id' => $this->getEntityId('item 3'), 'association' => 'children'],
            [
                'data' => [
                    ['type' => $entityType, 'id' => $this->getEntityId('item 1')]
                ]
            ]
        );

        $this->getEntityManager()->clear();
        /** @var TestEntity $updatedEntity */
        $updatedEntity = $this->getEntityManager()
            ->getRepository(TestEntity::class)
            ->findOneBy(['key' => 'item 3']);
        self::assertNotNull($updatedEntity);
        self::assertCount(2, $updatedEntity->getChildren());
        $ids = [$updatedEntity->getChildren()->get(0)->id, $updatedEntity->getChildren()->get(1)->id];
        sort($ids);
        self::assertSame($this->getReference('test_custom_id1')->id, $ids[0]);
        self::assertSame($this->getReference('test_custom_id2')->id, $ids[1]);
    }
}
