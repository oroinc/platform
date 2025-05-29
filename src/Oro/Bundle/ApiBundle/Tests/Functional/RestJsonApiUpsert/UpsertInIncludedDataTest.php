<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiUpsert;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestCustomCompositeIdentifier;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestCustomIdentifier;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestCustomIntIdentifier;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestUniqueKeyIdentifier;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UpsertInIncludedDataTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/upsert.yml'
        ]);
    }

    private function getEntityWithCustomIdentifier(string $key): TestCustomIdentifier
    {
        $entity = $this->getEntityManager()
            ->getRepository(TestCustomIdentifier::class)
            ->findOneBy(['key' => $key]);
        self::assertNotNull($entity);

        return $entity;
    }

    private function getEntityWithCustomIntIdentifier(int $key): TestCustomIntIdentifier
    {
        $entity = $this->getEntityManager()
            ->getRepository(TestCustomIntIdentifier::class)
            ->findOneBy(['key' => $key]);
        self::assertNotNull($entity);

        return $entity;
    }

    private function getEntityWithCustomCompositeIdentifier(string $key1, int $key2): TestCustomCompositeIdentifier
    {
        $entity = $this->getEntityManager()
            ->getRepository(TestCustomCompositeIdentifier::class)
            ->findOneBy(['key1' => $key1, 'key2' => $key2]);
        self::assertNotNull($entity);

        return $entity;
    }

    private function getCompositeId(string $key1, int $key2): string
    {
        return http_build_query(['key1' => $key1, 'key2' => $key2], '', ';');
    }

    public function testWhenEntityExists(): void
    {
        $entityType = $this->getEntityType(TestCustomIdentifier::class);
        $data = [
            'data'     => [
                'type'          => $entityType,
                'id'            => 'new item',
                'attributes'    => [
                    'name' => 'New Item'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => $entityType, 'id' => 'item 1']
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => $entityType,
                    'id'         => 'item 1',
                    'meta'       => ['upsert' => true],
                    'attributes' => [
                        'name' => 'Updated Item 1'
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $expectedData = $data;
        unset($expectedData['included'][0]['meta']);
        $this->assertResponseContains($expectedData, $response);

        $createdEntity = $this->getEntityWithCustomIdentifier('new item');
        self::assertSame('New Item', $createdEntity->name);
        self::assertSame(
            $this->getReference('test_custom_id1')->autoincrementKey,
            $createdEntity->getParent()->autoincrementKey
        );
        self::assertSame('Updated Item 1', $createdEntity->getParent()->name);
    }

    public function testWhenEntityExistsForIntId(): void
    {
        $entityType = $this->getEntityType(TestCustomIntIdentifier::class);
        $data = [
            'data'     => [
                'type'          => $entityType,
                'id'            => '100',
                'attributes'    => [
                    'name' => 'New Item'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => $entityType, 'id' => '10']
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => $entityType,
                    'id'         => '10',
                    'meta'       => ['upsert' => true],
                    'attributes' => [
                        'name' => 'Updated Item 1'
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $expectedData = $data;
        unset($expectedData['included'][0]['meta']);
        $this->assertResponseContains($expectedData, $response);

        $createdEntity = $this->getEntityWithCustomIntIdentifier(100);
        self::assertSame('New Item', $createdEntity->name);
        self::assertSame($this->getReference('test_custom_int_id1')->id, $createdEntity->getParent()->id);
        self::assertSame('Updated Item 1', $createdEntity->getParent()->name);
    }

    public function testWhenEntityExistsForCompositeId(): void
    {
        $entityType = $this->getEntityType(TestCustomCompositeIdentifier::class);
        $data = [
            'data'     => [
                'type'          => $entityType,
                'id'            => $this->getCompositeId('new item', 100),
                'attributes'    => [
                    'name' => 'New Item'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => $entityType, 'id' => $this->getCompositeId('item 1', 10)]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => $entityType,
                    'id'         => $this->getCompositeId('item 1', 10),
                    'meta'       => ['upsert' => true],
                    'attributes' => [
                        'name' => 'Updated Item 1'
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $expectedData = $data;
        unset($expectedData['included'][0]['meta']);
        $this->assertResponseContains($expectedData, $response);

        $createdEntity = $this->getEntityWithCustomCompositeIdentifier('new item', 100);
        self::assertSame('New Item', $createdEntity->name);
        self::assertSame($this->getReference('test_custom_composite_id1')->id, $createdEntity->getParent()->id);
        self::assertSame('Updated Item 1', $createdEntity->getParent()->name);
    }

    public function testWhenEntityDoesNotExist(): void
    {
        $entityType = $this->getEntityType(TestCustomIdentifier::class);
        $data = [
            'data'     => [
                'type'          => $entityType,
                'id'            => 'new item',
                'attributes'    => [
                    'name' => 'New Item'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => $entityType, 'id' => 'another new item']
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => $entityType,
                    'id'         => 'another new item',
                    'meta'       => ['upsert' => true],
                    'attributes' => [
                        'name' => 'Another New Item'
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $expectedData = $data;
        unset($expectedData['included'][0]['meta']);
        $this->assertResponseContains($expectedData, $response);

        $createdEntity = $this->getEntityWithCustomIdentifier('new item');
        self::assertSame('New Item', $createdEntity->name);
        self::assertSame('Another New Item', $createdEntity->getParent()->name);
    }

    public function testWhenEntityDoesNotExistForIntId(): void
    {
        $entityType = $this->getEntityType(TestCustomIntIdentifier::class);
        $data = [
            'data'     => [
                'type'          => $entityType,
                'id'            => '100',
                'attributes'    => [
                    'name' => 'New Item'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => $entityType, 'id' => '110']
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => $entityType,
                    'id'         => '110',
                    'meta'       => ['upsert' => true],
                    'attributes' => [
                        'name' => 'Another New Item'
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $expectedData = $data;
        unset($expectedData['included'][0]['meta']);
        $this->assertResponseContains($expectedData, $response);

        $createdEntity = $this->getEntityWithCustomIntIdentifier(100);
        self::assertSame('New Item', $createdEntity->name);
        self::assertSame('Another New Item', $createdEntity->getParent()->name);
    }

    public function testWhenEntityDoesNotExistForCompositeId(): void
    {
        $entityType = $this->getEntityType(TestCustomCompositeIdentifier::class);
        $data = [
            'data'     => [
                'type'          => $entityType,
                'id'            => $this->getCompositeId('new item', 100),
                'attributes'    => [
                    'name' => 'New Item'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => $entityType, 'id' => $this->getCompositeId('another new item', 110)]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => $entityType,
                    'id'         => $this->getCompositeId('another new item', 110),
                    'meta'       => ['upsert' => true],
                    'attributes' => [
                        'name' => 'Another New Item'
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $expectedData = $data;
        unset($expectedData['included'][0]['meta']);
        $this->assertResponseContains($expectedData, $response);

        $createdEntity = $this->getEntityWithCustomCompositeIdentifier('new item', 100);
        self::assertSame('New Item', $createdEntity->name);
        self::assertSame('Another New Item', $createdEntity->getParent()->name);
    }

    public function testByIdFieldWhenEntityExists(): void
    {
        $entityType = $this->getEntityType(TestCustomIdentifier::class);
        $data = [
            'data'     => [
                'type'          => $entityType,
                'id'            => 'new item',
                'attributes'    => [
                    'name' => 'New Item'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => $entityType, 'id' => 'item 1']
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => $entityType,
                    'id'         => 'item 1',
                    'meta'       => ['upsert' => ['id']],
                    'attributes' => [
                        'name' => 'Updated Item 1'
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $expectedData = $data;
        unset($expectedData['included'][0]['meta']);
        $this->assertResponseContains($expectedData, $response);

        $createdEntity = $this->getEntityWithCustomIdentifier('new item');
        self::assertSame('New Item', $createdEntity->name);
        self::assertSame(
            $this->getReference('test_custom_id1')->autoincrementKey,
            $createdEntity->getParent()->autoincrementKey
        );
        self::assertSame('Updated Item 1', $createdEntity->getParent()->name);
    }

    public function testByIdFieldWhenEntityExistsForIntId(): void
    {
        $entityType = $this->getEntityType(TestCustomIntIdentifier::class);
        $data = [
            'data'     => [
                'type'          => $entityType,
                'id'            => '100',
                'attributes'    => [
                    'name' => 'New Item'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => $entityType, 'id' => '10']
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => $entityType,
                    'id'         => '10',
                    'meta'       => ['upsert' => ['id']],
                    'attributes' => [
                        'name' => 'Updated Item 1'
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $expectedData = $data;
        unset($expectedData['included'][0]['meta']);
        $this->assertResponseContains($expectedData, $response);

        $createdEntity = $this->getEntityWithCustomIntIdentifier(100);
        self::assertSame('New Item', $createdEntity->name);
        self::assertSame($this->getReference('test_custom_int_id1')->id, $createdEntity->getParent()->id);
        self::assertSame('Updated Item 1', $createdEntity->getParent()->name);
    }

    public function testByIdFieldWhenEntityExistsForCompositeId(): void
    {
        $entityType = $this->getEntityType(TestCustomCompositeIdentifier::class);
        $data = [
            'data'     => [
                'type'          => $entityType,
                'id'            => $this->getCompositeId('new item', 100),
                'attributes'    => [
                    'name' => 'New Item'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => $entityType, 'id' => $this->getCompositeId('item 1', 10)]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => $entityType,
                    'id'         => $this->getCompositeId('item 1', 10),
                    'meta'       => ['upsert' => ['id']],
                    'attributes' => [
                        'name' => 'Updated Item 1'
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $expectedData = $data;
        unset($expectedData['included'][0]['meta']);
        $this->assertResponseContains($expectedData, $response);

        $createdEntity = $this->getEntityWithCustomCompositeIdentifier('new item', 100);
        self::assertSame('New Item', $createdEntity->name);
        self::assertSame($this->getReference('test_custom_composite_id1')->id, $createdEntity->getParent()->id);
        self::assertSame('Updated Item 1', $createdEntity->getParent()->name);
    }

    public function testByIdFieldWhenEntityDoesNotExist(): void
    {
        $entityType = $this->getEntityType(TestCustomIdentifier::class);
        $data = [
            'data'     => [
                'type'          => $entityType,
                'id'            => 'new item',
                'attributes'    => [
                    'name' => 'New Item'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => $entityType, 'id' => 'another new item']
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => $entityType,
                    'id'         => 'another new item',
                    'meta'       => ['upsert' => ['id']],
                    'attributes' => [
                        'name' => 'Another New Item'
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $expectedData = $data;
        unset($expectedData['included'][0]['meta']);
        $this->assertResponseContains($expectedData, $response);

        $createdEntity = $this->getEntityWithCustomIdentifier('new item');
        self::assertSame('New Item', $createdEntity->name);
        self::assertSame('Another New Item', $createdEntity->getParent()->name);
    }

    public function testByIdFieldWhenEntityDoesNotExistForIntId(): void
    {
        $entityType = $this->getEntityType(TestCustomIntIdentifier::class);
        $data = [
            'data'     => [
                'type'          => $entityType,
                'id'            => '100',
                'attributes'    => [
                    'name' => 'New Item'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => $entityType, 'id' => '110']
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => $entityType,
                    'id'         => '110',
                    'meta'       => ['upsert' => ['id']],
                    'attributes' => [
                        'name' => 'Another New Item'
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $expectedData = $data;
        unset($expectedData['included'][0]['meta']);
        $this->assertResponseContains($expectedData, $response);

        $createdEntity = $this->getEntityWithCustomIntIdentifier(100);
        self::assertSame('New Item', $createdEntity->name);
        self::assertSame('Another New Item', $createdEntity->getParent()->name);
    }

    public function testByIdFieldWhenEntityDoesNotExistForCompositeId(): void
    {
        $entityType = $this->getEntityType(TestCustomCompositeIdentifier::class);
        $data = [
            'data'     => [
                'type'          => $entityType,
                'id'            => $this->getCompositeId('new item', 100),
                'attributes'    => [
                    'name' => 'New Item'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => $entityType, 'id' => $this->getCompositeId('another new item', 110)]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => $entityType,
                    'id'         => $this->getCompositeId('another new item', 110),
                    'meta'       => ['upsert' => ['id']],
                    'attributes' => [
                        'name' => 'Another New Item'
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $expectedData = $data;
        unset($expectedData['included'][0]['meta']);
        $this->assertResponseContains($expectedData, $response);

        $createdEntity = $this->getEntityWithCustomCompositeIdentifier('new item', 100);
        self::assertSame('New Item', $createdEntity->name);
        self::assertSame('Another New Item', $createdEntity->getParent()->name);
    }

    public function testTryToForEntityWithAutoGeneratedId(): void
    {
        $entityType = $this->getEntityType(TestUniqueKeyIdentifier::class);

        $response = $this->patch(
            ['entity' => $entityType, 'id' => '<toString(@test_unique_key_id3->id)>'],
            [
                'data'     => [
                    'type'          => $entityType,
                    'id'            => '<toString(@test_unique_key_id3->id)>',
                    'relationships' => [
                        'parent' => [
                            'data' => ['type' => $entityType, 'id' => 'include1']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => $entityType,
                        'id'         => 'include1',
                        'meta'       => ['upsert' => true],
                        'attributes' => [
                            'key1' => 'item 1',
                            'key2' => 10,
                            'key3' => 'item 1',
                            'key4' => 10,
                            'name' => 'Updated Item 1'
                        ]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'value constraint',
                'detail' => 'The upsert operation cannot use the entity identifier to find an entity.',
                'source' => ['pointer' => '/included/0/meta/upsert']
            ],
            $response
        );
    }

    public function testTryToByIdFieldForEntityWithAutoGeneratedId(): void
    {
        $entityType = $this->getEntityType(TestUniqueKeyIdentifier::class);

        $response = $this->patch(
            ['entity' => $entityType, 'id' => '<toString(@test_unique_key_id3->id)>'],
            [
                'data'     => [
                    'type'          => $entityType,
                    'id'            => '<toString(@test_unique_key_id3->id)>',
                    'relationships' => [
                        'parent' => [
                            'data' => ['type' => $entityType, 'id' => 'include1']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => $entityType,
                        'id'         => 'include1',
                        'meta'       => ['upsert' => ['id']],
                        'attributes' => [
                            'key1' => 'item 1',
                            'key2' => 10,
                            'key3' => 'item 1',
                            'key4' => 10,
                            'name' => 'Updated Item 1'
                        ]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'value constraint',
                'detail' => 'The upsert operation cannot use the entity identifier to find an entity.',
                'source' => ['pointer' => '/included/0/meta/upsert']
            ],
            $response
        );
    }

    public function testTryToByFieldWithoutUniqueConstraint(): void
    {
        $entityType = $this->getEntityType(TestUniqueKeyIdentifier::class);

        $response = $this->patch(
            ['entity' => $entityType, 'id' => '<toString(@test_unique_key_id3->id)>'],
            [
                'data'     => [
                    'type'          => $entityType,
                    'id'            => '<toString(@test_unique_key_id3->id)>',
                    'relationships' => [
                        'parent' => [
                            'data' => ['type' => $entityType, 'id' => 'include1']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => $entityType,
                        'id'         => 'include1',
                        'meta'       => ['upsert' => ['key1']],
                        'attributes' => [
                            'key1' => 'item 1',
                            'key2' => 10,
                            'key3' => 'item 1',
                            'key4' => 10,
                            'name' => 'Updated Item 1'
                        ]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'value constraint',
                'detail' => 'The upsert operation cannot use this field to find an entity.',
                'source' => ['pointer' => '/included/0/meta/upsert']
            ],
            $response
        );
    }

    public function testByIntFieldWithUniqueConstraint(): void
    {
        $entityType = $this->getEntityType(TestUniqueKeyIdentifier::class);
        $data = [
            'data'     => [
                'type'          => $entityType,
                'id'            => '<toString(@test_unique_key_id3->id)>',
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => $entityType, 'id' => 'include1']
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => $entityType,
                    'id'         => 'include1',
                    'meta'       => ['upsert' => ['key4']],
                    'attributes' => [
                        'key1' => 'item 1',
                        'key2' => 10,
                        'key3' => 'item 1',
                        'key4' => 10,
                        'name' => 'Updated Item 1'
                    ]
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => $entityType, 'id' => '<toString(@test_unique_key_id3->id)>'],
            $data
        );

        $updatedEntityId = $this->getReference('test_unique_key_id1')->id;

        $expectedData = $data;
        unset($expectedData['included'][0]['meta']);
        $expectedData['data']['relationships']['parent']['data']['id'] = (string)$updatedEntityId;
        $expectedData['included'][0]['id'] = (string)$updatedEntityId;
        $this->assertResponseContains($expectedData, $response);

        $updatedEntity = $this->getEntityManager()->find(TestUniqueKeyIdentifier::class, $updatedEntityId);
        self::assertSame('Updated Item 1', $updatedEntity->name);
    }

    public function testByConfiguredFieldWithUniqueConstraintAndIncludedEntityReferencesPrimaryEntity(): void
    {
        $entityType = $this->getEntityType(TestUniqueKeyIdentifier::class);
        $data = [
            'data'     => [
                'type' => $entityType,
                'id'   => '<toString(@test_unique_key_id3->id)>'
            ],
            'included' => [
                [
                    'type'          => $entityType,
                    'id'            => 'include1',
                    'meta'          => ['upsert' => ['key4']],
                    'attributes'    => [
                        'key1' => 'item 1',
                        'key2' => 10,
                        'key3' => 'item 1',
                        'key4' => 10,
                        'name' => 'Updated Item 1'
                    ],
                    'relationships' => [
                        'children' => [
                            'data' => [['type' => $entityType, 'id' => '<toString(@test_unique_key_id3->id)>']]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => $entityType, 'id' => '<toString(@test_unique_key_id3->id)>'],
            $data
        );

        $updatedEntityId = $this->getReference('test_unique_key_id1')->id;

        $expectedData = $data;
        unset($expectedData['included'][0]['meta']);
        $expectedData['data']['relationships']['parent']['data']['id'] = (string)$updatedEntityId;
        $expectedData['included'][0]['id'] = (string)$updatedEntityId;
        $this->assertResponseContains($expectedData, $response);

        $updatedEntity = $this->getEntityManager()->find(TestUniqueKeyIdentifier::class, $updatedEntityId);
        self::assertSame('Updated Item 1', $updatedEntity->name);
    }

    public function testTryToByConfiguredFieldWithoutUniqueConstraintWhenSeveralEntitiesFound(): void
    {
        $entityType = $this->getEntityType(TestUniqueKeyIdentifier::class);

        $response = $this->patch(
            ['entity' => $entityType, 'id' => '<toString(@test_unique_key_id3->id)>'],
            [
                'data'     => [
                    'type'          => $entityType,
                    'id'            => '<toString(@test_unique_key_id3->id)>',
                    'relationships' => [
                        'parent' => [
                            'data' => ['type' => $entityType, 'id' => 'include1']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => $entityType,
                        'id'         => 'include1',
                        'meta'       => ['upsert' => ['key7']],
                        'attributes' => [
                            'key1' => 'item 1',
                            'key2' => 10,
                            'key3' => 'item 1',
                            'key4' => 10,
                            'key7' => 'item 1',
                            'name' => 'Updated Item 1'
                        ]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'conflict constraint',
                'detail' => 'The upsert operation founds more than one entity.',
                'source' => ['pointer' => '/included/0']
            ],
            $response,
            Response::HTTP_CONFLICT
        );
    }

    public function testTryToWhenEntityExistsAndAccessToItIsDenied(): void
    {
        $entityType = $this->getEntityType(TestCustomIntIdentifier::class);

        $response = $this->patch(
            ['entity' => $entityType, 'id' => '50'],
            [
                'data'     => [
                    'type'          => $entityType,
                    'id'            => '50',
                    'relationships' => [
                        'children' => [
                            'data' => [['type' => $entityType, 'id' => '40']]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => $entityType,
                        'id'         => '40',
                        'meta'       => ['upsert' => true],
                        'attributes' => [
                            'name' => 'Updated Item 4'
                        ]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the entity.',
                'source' => ['pointer' => '/included/0']
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToByFieldWhenEntityExistsAndAccessToItIsDenied(): void
    {
        $entityType = $this->getEntityType(TestUniqueKeyIdentifier::class);

        $response = $this->patch(
            ['entity' => $entityType, 'id' => '<toString(@test_unique_key_id5->id)>'],
            [
                'data'     => [
                    'type'          => $entityType,
                    'id'            => '<toString(@test_unique_key_id5->id)>',
                    'relationships' => [
                        'children' => [
                            'data' => [['type' => $entityType, 'id' => 'child']]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => $entityType,
                        'id'         => 'child',
                        'meta'       => ['upsert' => ['key1', 'key2']],
                        'attributes' => [
                            'key1' => 'item 4',
                            'key2' => 40,
                            'name' => 'Updated Item 4'
                        ]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the entity.',
                'source' => ['pointer' => '/included/0']
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }
}
