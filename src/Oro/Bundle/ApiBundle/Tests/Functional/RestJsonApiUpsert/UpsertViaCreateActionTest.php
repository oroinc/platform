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
class UpsertViaCreateActionTest extends RestJsonApiTestCase
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
            'data' => [
                'type'       => $entityType,
                'id'         => 'item 1',
                'meta'       => ['upsert' => true],
                'attributes' => [
                    'name' => 'Updated Item 1'
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data, [], false);
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertResponseContentTypeEquals($response, $this->getResponseContentType());

        $expectedData = $data;
        unset($expectedData['data']['meta']);
        $this->assertResponseContains($expectedData, $response);

        $updatedEntity = $this->getEntityWithCustomIdentifier('item 1');
        self::assertSame('Updated Item 1', $updatedEntity->name);
    }

    public function testWhenEntityExistsForIntId(): void
    {
        $entityType = $this->getEntityType(TestCustomIntIdentifier::class);
        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => '10',
                'meta'       => ['upsert' => true],
                'attributes' => [
                    'name' => 'Updated Item 1'
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data, [], false);
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertResponseContentTypeEquals($response, $this->getResponseContentType());

        $expectedData = $data;
        unset($expectedData['data']['meta']);
        $this->assertResponseContains($expectedData, $response);

        $updatedEntity = $this->getEntityWithCustomIntIdentifier(10);
        self::assertSame('Updated Item 1', $updatedEntity->name);
    }

    public function testWhenEntityExistsForCompositeId(): void
    {
        $entityType = $this->getEntityType(TestCustomCompositeIdentifier::class);
        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => $this->getCompositeId('item 1', 10),
                'meta'       => ['upsert' => true],
                'attributes' => [
                    'name' => 'Updated Item 1'
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data, [], false);
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertResponseContentTypeEquals($response, $this->getResponseContentType());

        $expectedData = $data;
        unset($expectedData['data']['meta']);
        $this->assertResponseContains($expectedData, $response);

        $updatedEntity = $this->getEntityWithCustomCompositeIdentifier('item 1', 10);
        self::assertSame('Updated Item 1', $updatedEntity->name);
    }

    public function testWhenEntityDoesNotExist(): void
    {
        $entityType = $this->getEntityType(TestCustomIdentifier::class);
        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => 'new item',
                'meta'       => ['upsert' => true],
                'attributes' => [
                    'name' => 'New Item'
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $expectedData = $data;
        unset($expectedData['data']['meta']);
        $this->assertResponseContains($expectedData, $response);

        $createdEntity = $this->getEntityWithCustomIdentifier('new item');
        self::assertSame('New Item', $createdEntity->name);
    }

    public function testWhenEntityDoesNotExistForIntId(): void
    {
        $entityType = $this->getEntityType(TestCustomIntIdentifier::class);
        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => '100',
                'meta'       => ['upsert' => true],
                'attributes' => [
                    'name' => 'New Item'
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $expectedData = $data;
        unset($expectedData['data']['meta']);
        $this->assertResponseContains($expectedData, $response);

        $createdEntity = $this->getEntityWithCustomIntIdentifier(100);
        self::assertSame('New Item', $createdEntity->name);
    }

    public function testWhenEntityDoesNotExistForCompositeId(): void
    {
        $entityType = $this->getEntityType(TestCustomCompositeIdentifier::class);
        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => $this->getCompositeId('new item', 100),
                'meta'       => ['upsert' => true],
                'attributes' => [
                    'name' => 'New Item'
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $expectedData = $data;
        unset($expectedData['data']['meta']);
        $this->assertResponseContains($expectedData, $response);

        $createdEntity = $this->getEntityWithCustomCompositeIdentifier('new item', 100);
        self::assertSame('New Item', $createdEntity->name);
    }

    public function testTryToForEntityWithAutoGeneratedId(): void
    {
        $entityType = $this->getEntityType(TestUniqueKeyIdentifier::class);

        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type'       => $entityType,
                    'meta'       => ['upsert' => true],
                    'attributes' => [
                        'key1' => 'item 1',
                        'key2' => 10,
                        'key3' => 'item 1',
                        'key4' => 10,
                        'name' => 'Updated Item 1'
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
                'source' => ['pointer' => '/meta/upsert']
            ],
            $response
        );
    }

    public function testByIdField(): void
    {
        $entityType = $this->getEntityType(TestCustomIdentifier::class);
        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => 'item 1',
                'meta'       => ['upsert' => ['id']],
                'attributes' => [
                    'name' => 'Updated Item 1'
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data, [], false);
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertResponseContentTypeEquals($response, $this->getResponseContentType());

        $expectedData = $data;
        unset($expectedData['data']['meta']);
        $this->assertResponseContains($expectedData, $response);

        $updatedEntity = $this->getEntityWithCustomIdentifier('item 1');
        self::assertSame('Updated Item 1', $updatedEntity->name);
    }

    public function testTryToByIdFieldForEntityWithAutoGeneratedId(): void
    {
        $entityType = $this->getEntityType(TestUniqueKeyIdentifier::class);

        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type'       => $entityType,
                    'meta'       => ['upsert' => ['id']],
                    'attributes' => [
                        'key1' => 'item 1',
                        'key2' => 10,
                        'key3' => 'item 1',
                        'key4' => 10,
                        'name' => 'Updated Item 1'
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
                'source' => ['pointer' => '/meta/upsert']
            ],
            $response
        );
    }

    public function testTryToByToOneAssociation(): void
    {
        $entityType = $this->getEntityType(TestCustomIdentifier::class);

        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type'       => $entityType,
                    'id'         => 'item 1',
                    'meta'       => ['upsert' => ['parent']],
                    'attributes' => [
                        'name' => 'Updated Item 1'
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
                'source' => ['pointer' => '/meta/upsert']
            ],
            $response
        );
    }

    public function testTryToByToManyAssociation(): void
    {
        $entityType = $this->getEntityType(TestCustomIdentifier::class);

        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type'       => $entityType,
                    'id'         => 'item 1',
                    'meta'       => ['upsert' => ['children']],
                    'attributes' => [
                        'name' => 'Updated Item 1'
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
                'source' => ['pointer' => '/meta/upsert']
            ],
            $response
        );
    }

    public function testByToOneAssociationThatAddedToUpsertConfigAndWhenEntityExists(): void
    {
        $entityType = $this->getEntityType(TestUniqueKeyIdentifier::class);
        $data = [
            'data' => [
                'type'          => $entityType,
                'meta'          => ['upsert' => ['key6', 'parent']],
                'attributes'    => [
                    'key1' => 'item 3',
                    'key2' => 30,
                    'key3' => 'item 3',
                    'key4' => 30,
                    'key6' => 'item 3',
                    'name' => 'Updated Item 3'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => $entityType, 'id' => '<toString(@test_unique_key_id1->id)>']
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data, [], false);
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertResponseContentTypeEquals($response, $this->getResponseContentType());

        $expectedData = $data;
        unset($expectedData['data']['meta']);
        $this->assertResponseContains($expectedData, $response);

        $updatedEntity = $this->getEntityManager()
            ->find(TestUniqueKeyIdentifier::class, $this->getReference('test_unique_key_id3')->id);
        self::assertSame('Updated Item 3', $updatedEntity->name);
    }

    public function testByToOneAssociationThatAddedToUpsertConfigAndWhenEntityDoesNotExist(): void
    {
        $entityType = $this->getEntityType(TestUniqueKeyIdentifier::class);
        $data = [
            'data' => [
                'type'          => $entityType,
                'meta'          => ['upsert' => ['key6', 'parent']],
                'attributes'    => [
                    'key1' => 'new item',
                    'key2' => 100,
                    'key3' => 'new item',
                    'key4' => 100,
                    'key6' => 'item 3',
                    'name' => 'New Item'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => $entityType, 'id' => '<toString(@test_unique_key_id2->id)>']
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);
        $createdEntityId = (int)$this->getResourceId($response);

        $expectedData = $data;
        $expectedData['data']['id'] = (string)$createdEntityId;
        unset($expectedData['data']['meta']);
        $this->assertResponseContains($expectedData, $response);

        $createdEntity = $this->getEntityManager()
            ->find(TestUniqueKeyIdentifier::class, $createdEntityId);
        self::assertSame('New Item', $createdEntity->name);
    }

    public function testTryToByToManyAssociationThatAddedToUpsertConfig(): void
    {
        $entityType = $this->getEntityType(TestUniqueKeyIdentifier::class);

        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type'          => $entityType,
                    'meta'          => ['upsert' => ['key6', 'children']],
                    'attributes'    => [
                        'key1' => 'item 3',
                        'key2' => 30,
                        'key3' => 'item 3',
                        'key4' => 30,
                        'key6' => 'item 3',
                        'name' => 'Updated Item 3'
                    ],
                    'relationships' => [
                        'children' => [
                            'data' => [
                                ['type' => $entityType, 'id' => '<toString(@test_unique_key_id2->id)>']
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
                'title'  => 'value constraint',
                'detail' => 'The "children" field is not allowed because it is to-many association.',
                'source' => ['pointer' => '/meta/upsert']
            ],
            $response
        );
    }

    public function testTryToByFieldWithoutUniqueConstraint(): void
    {
        $entityType = $this->getEntityType(TestUniqueKeyIdentifier::class);

        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type'       => $entityType,
                    'meta'       => ['upsert' => ['key1']],
                    'attributes' => [
                        'key1' => 'item 1',
                        'key2' => 10,
                        'key3' => 'item 1',
                        'key4' => 10,
                        'name' => 'Updated Item 1'
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
                'source' => ['pointer' => '/meta/upsert']
            ],
            $response
        );
    }

    public function testByStringFieldWithUniqueConstraint(): void
    {
        $entityType = $this->getEntityType(TestUniqueKeyIdentifier::class);
        $data = [
            'data' => [
                'type'       => $entityType,
                'meta'       => ['upsert' => ['key3']],
                'attributes' => [
                    'key1' => 'item 1',
                    'key2' => 10,
                    'key3' => 'item 1',
                    'key4' => 10,
                    'name' => 'Updated Item 1'
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data, [], false);
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertResponseContentTypeEquals($response, $this->getResponseContentType());

        $expectedData = $data;
        unset($expectedData['data']['meta']);
        $this->assertResponseContains($expectedData, $response);

        $updatedEntity = $this->getEntityManager()
            ->find(TestUniqueKeyIdentifier::class, $this->getReference('test_unique_key_id1')->id);
        self::assertSame('Updated Item 1', $updatedEntity->name);
    }

    public function testByIntFieldWithUniqueConstraint(): void
    {
        $entityType = $this->getEntityType(TestUniqueKeyIdentifier::class);
        $data = [
            'data' => [
                'type'       => $entityType,
                'meta'       => ['upsert' => ['key4']],
                'attributes' => [
                    'key1' => 'item 1',
                    'key2' => 10,
                    'key3' => 'item 1',
                    'key4' => 10,
                    'name' => 'Updated Item 1'
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data, [], false);
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertResponseContentTypeEquals($response, $this->getResponseContentType());

        $expectedData = $data;
        unset($expectedData['data']['meta']);
        $this->assertResponseContains($expectedData, $response);

        $updatedEntity = $this->getEntityManager()
            ->find(TestUniqueKeyIdentifier::class, $this->getReference('test_unique_key_id1')->id);
        self::assertSame('Updated Item 1', $updatedEntity->name);
    }

    public function testTryToByConfiguredFieldWithoutUniqueConstraintWhenSeveralEntitiesFound(): void
    {
        $entityType = $this->getEntityType(TestUniqueKeyIdentifier::class);

        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type'       => $entityType,
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
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'conflict constraint',
                'detail' => 'The upsert operation founds more than one entity.'
            ],
            $response,
            Response::HTTP_CONFLICT
        );
    }

    public function testTryToWhenEntityExistsAndAccessToItIsDenied(): void
    {
        $entityType = $this->getEntityType(TestCustomIntIdentifier::class);

        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type'       => $entityType,
                    'id'         => '40',
                    'meta'       => ['upsert' => true],
                    'attributes' => [
                        'name' => 'Updated Item 4'
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToByFieldWhenEntityExistsAndAccessToItIsDenied(): void
    {
        $entityType = $this->getEntityType(TestUniqueKeyIdentifier::class);

        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type'       => $entityType,
                    'meta'       => ['upsert' => ['key1', 'key2']],
                    'attributes' => [
                        'key1' => 'item 4',
                        'key2' => 40,
                        'name' => 'Updated Item 4'
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access denied exception',
                'detail' => 'No access to the entity.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }
}
