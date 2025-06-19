<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiUpsert;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestCustomCompositeIdentifier;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestCustomIdentifier;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestCustomIntIdentifier;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiUpdateListTestCase;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UpdateListForUpsertTest extends RestJsonApiUpdateListTestCase
{
    #[\Override]
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

    public function testUpsert(): void
    {
        $entityType = $this->getEntityType(TestCustomIdentifier::class);

        $this->processUpdateList(
            TestCustomIdentifier::class,
            [
                'data' => [
                    [
                        'type'       => $entityType,
                        'id'         => 'item 1',
                        'meta'       => ['upsert' => true],
                        'attributes' => [
                            'name' => 'Updated Item 1'
                        ]
                    ],
                    [
                        'type'       => $entityType,
                        'id'         => 'new item',
                        'meta'       => ['upsert' => true],
                        'attributes' => [
                            'name' => 'New Item'
                        ]
                    ]
                ]
            ]
        );

        $updatedEntity = $this->getEntityWithCustomIdentifier('item 1');
        self::assertSame('Updated Item 1', $updatedEntity->name);
        $createdEntity = $this->getEntityWithCustomIdentifier('new item');
        self::assertSame('New Item', $createdEntity->name);
    }

    public function testUpsertForIntId(): void
    {
        $entityType = $this->getEntityType(TestCustomIntIdentifier::class);

        $this->processUpdateList(
            TestCustomIntIdentifier::class,
            [
                'data' => [
                    [
                        'type'       => $entityType,
                        'id'         => '10',
                        'meta'       => ['upsert' => true],
                        'attributes' => [
                            'name' => 'Updated Item 1'
                        ]
                    ],
                    [
                        'type'       => $entityType,
                        'id'         => '100',
                        'meta'       => ['upsert' => true],
                        'attributes' => [
                            'name' => 'New Item'
                        ]
                    ]
                ]
            ]
        );

        $updatedEntity = $this->getEntityWithCustomIntIdentifier(10);
        self::assertSame('Updated Item 1', $updatedEntity->name);
        $createdEntity = $this->getEntityWithCustomIntIdentifier(100);
        self::assertSame('New Item', $createdEntity->name);
    }

    public function testUpsertForCompositeId(): void
    {
        $entityType = $this->getEntityType(TestCustomCompositeIdentifier::class);

        $this->processUpdateList(
            TestCustomCompositeIdentifier::class,
            [
                'data' => [
                    [
                        'type'       => $entityType,
                        'id'         => $this->getCompositeId('item 1', 10),
                        'meta'       => ['upsert' => true],
                        'attributes' => [
                            'name' => 'Updated Item 1'
                        ]
                    ],
                    [
                        'type'       => $entityType,
                        'id'         => $this->getCompositeId('new item', 100),
                        'meta'       => ['upsert' => true],
                        'attributes' => [
                            'name' => 'New Item'
                        ]
                    ]
                ]
            ]
        );

        $updatedEntity = $this->getEntityWithCustomCompositeIdentifier('item 1', 10);
        self::assertSame('Updated Item 1', $updatedEntity->name);
        $createdEntity = $this->getEntityWithCustomCompositeIdentifier('new item', 100);
        self::assertSame('New Item', $createdEntity->name);
    }

    public function testTryToUpsertWhenRequestDataHasValidationErrors(): void
    {
        $entityType = $this->getEntityType(TestCustomIdentifier::class);

        $operationId = $this->processUpdateList(
            TestCustomIdentifier::class,
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => 'item 1',
                        'meta' => ['upsert' => true],
                        'attributes' => [
                            'name' => null
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => 'new item',
                        'meta' => ['upsert' => true]
                    ]
                ]
            ],
            false
        );

        $this->assertAsyncOperationErrors(
            [
                [
                    'id'     => $operationId . '-1-1',
                    'status' => 400,
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/0/attributes/name']
                ],
                [
                    'id'     => $operationId . '-1-2',
                    'status' => 400,
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/1/attributes/name']
                ]
            ],
            $operationId
        );
    }

    public function testUpsertInIncludedDataWhenEntityExists(): void
    {
        $entityType = $this->getEntityType(TestCustomIdentifier::class);

        $this->processUpdateList(
            TestCustomIdentifier::class,
            [
                'data'     => [
                    [
                        'type'          => $entityType,
                        'id'            => 'new item',
                        'attributes'    => [
                            'name' => 'New Item'
                        ],
                        'relationships' => [
                            'children' => [
                                'data' => [
                                    ['type' => $entityType, 'id' => 'item 1'],
                                    ['type' => $entityType, 'id' => 'another new item']
                                ]
                            ]
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
                    ],
                    [
                        'type'       => $entityType,
                        'id'         => 'another new item',
                        'meta'       => ['upsert' => true],
                        'attributes' => [
                            'name' => 'Another New Item'
                        ]
                    ]
                ]
            ]
        );

        $createdEntity = $this->getEntityWithCustomIdentifier('new item');
        self::assertSame('New Item', $createdEntity->name);
        $children = $createdEntity->getChildren()->toArray();
        self::assertCount(2, $children);
        $childIds = array_map(fn (TestCustomIdentifier $child) => $child->autoincrementKey, $children);
        sort($childIds);
        /** @var TestCustomIdentifier $updatedChildEntity */
        $updatedChildEntity = $this->getReference('test_custom_id1');
        $createdChildEntity = $this->getEntityWithCustomIdentifier('another new item');
        self::assertSame([$updatedChildEntity->autoincrementKey, $createdChildEntity->autoincrementKey], $childIds);
        self::assertSame('Updated Item 1', $updatedChildEntity->name);
        self::assertSame('Another New Item', $createdChildEntity->name);
    }

    public function testUpsertInIncludedDataWhenEntityExistsForIntId(): void
    {
        $entityType = $this->getEntityType(TestCustomIntIdentifier::class);

        $this->processUpdateList(
            TestCustomIntIdentifier::class,
            [
                'data'     => [
                    [
                        'type'          => $entityType,
                        'id'            => '100',
                        'attributes'    => [
                            'name' => 'New Item'
                        ],
                        'relationships' => [
                            'children' => [
                                'data' => [
                                    ['type' => $entityType, 'id' => '10'],
                                    ['type' => $entityType, 'id' => '110']
                                ]
                            ]
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
                    ],
                    [
                        'type'       => $entityType,
                        'id'         => '110',
                        'meta'       => ['upsert' => true],
                        'attributes' => [
                            'name' => 'Another New Item'
                        ]
                    ]
                ]
            ]
        );

        $createdEntity = $this->getEntityWithCustomIntIdentifier(100);
        self::assertSame('New Item', $createdEntity->name);
        $children = $createdEntity->getChildren()->toArray();
        self::assertCount(2, $children);
        $childIds = array_map(fn (TestCustomIntIdentifier $child) => $child->id, $children);
        sort($childIds);
        /** @var TestCustomIntIdentifier $updatedChildEntity */
        $updatedChildEntity = $this->getReference('test_custom_int_id1');
        $createdChildEntity = $this->getEntityWithCustomIntIdentifier(110);
        self::assertSame([$updatedChildEntity->id, $createdChildEntity->id], $childIds);
        self::assertSame('Updated Item 1', $updatedChildEntity->name);
        self::assertSame('Another New Item', $createdChildEntity->name);
    }

    public function testUpsertInIncludedDataWhenEntityExistsForCompositeId(): void
    {
        $entityType = $this->getEntityType(TestCustomCompositeIdentifier::class);

        $this->processUpdateList(
            TestCustomCompositeIdentifier::class,
            [
                'data'     => [
                    [
                        'type'          => $entityType,
                        'id'            => $this->getCompositeId('new item', 100),
                        'attributes'    => [
                            'name' => 'New Item'
                        ],
                        'relationships' => [
                            'children' => [
                                'data' => [
                                    ['type' => $entityType, 'id' => $this->getCompositeId('item 1', 10)],
                                    ['type' => $entityType, 'id' => $this->getCompositeId('another new item', 110)]
                                ]
                            ]
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
                    ],
                    [
                        'type'       => $entityType,
                        'id'         => $this->getCompositeId('another new item', 110),
                        'meta'       => ['upsert' => true],
                        'attributes' => [
                            'name' => 'Another New Item'
                        ]
                    ]
                ]
            ]
        );

        $createdEntity = $this->getEntityWithCustomCompositeIdentifier('new item', 100);
        self::assertSame('New Item', $createdEntity->name);
        $children = $createdEntity->getChildren()->toArray();
        self::assertCount(2, $children);
        $childIds = array_map(fn (TestCustomCompositeIdentifier $child) => $child->id, $children);
        sort($childIds);
        /** @var TestCustomCompositeIdentifier $updatedChildEntity */
        $updatedChildEntity = $this->getReference('test_custom_composite_id1');
        $createdChildEntity = $this->getEntityWithCustomCompositeIdentifier('another new item', 110);
        self::assertSame([$updatedChildEntity->id, $createdChildEntity->id], $childIds);
        self::assertSame('Updated Item 1', $updatedChildEntity->name);
        self::assertSame('Another New Item', $createdChildEntity->name);
    }

    public function testUpsertInIncludedDataByIdFieldWhenEntityExists(): void
    {
        $entityType = $this->getEntityType(TestCustomIdentifier::class);

        $this->processUpdateList(
            TestCustomIdentifier::class,
            [
                'data'     => [
                    [
                        'type'          => $entityType,
                        'id'            => 'new item',
                        'attributes'    => [
                            'name' => 'New Item'
                        ],
                        'relationships' => [
                            'children' => [
                                'data' => [
                                    ['type' => $entityType, 'id' => 'item 1'],
                                    ['type' => $entityType, 'id' => 'another new item']
                                ]
                            ]
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
                    ],
                    [
                        'type'       => $entityType,
                        'id'         => 'another new item',
                        'meta'       => ['upsert' => ['id']],
                        'attributes' => [
                            'name' => 'Another New Item'
                        ]
                    ]
                ]
            ]
        );

        $createdEntity = $this->getEntityWithCustomIdentifier('new item');
        self::assertSame('New Item', $createdEntity->name);
        $children = $createdEntity->getChildren()->toArray();
        self::assertCount(2, $children);
        $childIds = array_map(fn (TestCustomIdentifier $child) => $child->autoincrementKey, $children);
        sort($childIds);
        /** @var TestCustomIdentifier $updatedChildEntity */
        $updatedChildEntity = $this->getReference('test_custom_id1');
        $createdChildEntity = $this->getEntityWithCustomIdentifier('another new item');
        self::assertSame([$updatedChildEntity->autoincrementKey, $createdChildEntity->autoincrementKey], $childIds);
        self::assertSame('Updated Item 1', $updatedChildEntity->name);
        self::assertSame('Another New Item', $createdChildEntity->name);
    }

    public function testUpsertInIncludedDataByIdFieldWhenEntityExistsForIntId(): void
    {
        $entityType = $this->getEntityType(TestCustomIntIdentifier::class);

        $this->processUpdateList(
            TestCustomIntIdentifier::class,
            [
                'data'     => [
                    [
                        'type'          => $entityType,
                        'id'            => '100',
                        'attributes'    => [
                            'name' => 'New Item'
                        ],
                        'relationships' => [
                            'children' => [
                                'data' => [
                                    ['type' => $entityType, 'id' => '10'],
                                    ['type' => $entityType, 'id' => '110']
                                ]
                            ]
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
                    ],
                    [
                        'type'       => $entityType,
                        'id'         => '110',
                        'meta'       => ['upsert' => ['id']],
                        'attributes' => [
                            'name' => 'Another New Item'
                        ]
                    ]
                ]
            ]
        );

        $createdEntity = $this->getEntityWithCustomIntIdentifier(100);
        self::assertSame('New Item', $createdEntity->name);
        $children = $createdEntity->getChildren()->toArray();
        self::assertCount(2, $children);
        $childIds = array_map(fn (TestCustomIntIdentifier $child) => $child->id, $children);
        sort($childIds);
        /** @var TestCustomIntIdentifier $updatedChildEntity */
        $updatedChildEntity = $this->getReference('test_custom_int_id1');
        $createdChildEntity = $this->getEntityWithCustomIntIdentifier(110);
        self::assertSame([$updatedChildEntity->id, $createdChildEntity->id], $childIds);
        self::assertSame('Updated Item 1', $updatedChildEntity->name);
        self::assertSame('Another New Item', $createdChildEntity->name);
    }

    public function testUpsertInIncludedDataByIdFieldWhenEntityExistsForCompositeId(): void
    {
        $entityType = $this->getEntityType(TestCustomCompositeIdentifier::class);

        $this->processUpdateList(
            TestCustomCompositeIdentifier::class,
            [
                'data'     => [
                    [
                        'type'          => $entityType,
                        'id'            => $this->getCompositeId('new item', 100),
                        'attributes'    => [
                            'name' => 'New Item'
                        ],
                        'relationships' => [
                            'children' => [
                                'data' => [
                                    ['type' => $entityType, 'id' => $this->getCompositeId('item 1', 10)],
                                    ['type' => $entityType, 'id' => $this->getCompositeId('another new item', 110)]
                                ]
                            ]
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
                    ],
                    [
                        'type'       => $entityType,
                        'id'         => $this->getCompositeId('another new item', 110),
                        'meta'       => ['upsert' => ['id']],
                        'attributes' => [
                            'name' => 'Another New Item'
                        ]
                    ]
                ]
            ]
        );

        $createdEntity = $this->getEntityWithCustomCompositeIdentifier('new item', 100);
        self::assertSame('New Item', $createdEntity->name);
        $children = $createdEntity->getChildren()->toArray();
        self::assertCount(2, $children);
        $childIds = array_map(fn (TestCustomCompositeIdentifier $child) => $child->id, $children);
        sort($childIds);
        /** @var TestCustomCompositeIdentifier $updatedChildEntity */
        $updatedChildEntity = $this->getReference('test_custom_composite_id1');
        $createdChildEntity = $this->getEntityWithCustomCompositeIdentifier('another new item', 110);
        self::assertSame([$updatedChildEntity->id, $createdChildEntity->id], $childIds);
        self::assertSame('Updated Item 1', $updatedChildEntity->name);
        self::assertSame('Another New Item', $createdChildEntity->name);
    }
}
