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
class UpsertViaUpdateActionTest extends RestJsonApiTestCase
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

        $response = $this->patch(['entity' => $entityType, 'id' => 'item 1'], $data);

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

        $response = $this->patch(['entity' => $entityType, 'id' => '10'], $data);

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

        $response = $this->patch(['entity' => $entityType, 'id' => $this->getCompositeId('item 1', 10)], $data);

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

        $response = $this->patch(['entity' => $entityType, 'id' => 'new item'], $data, [], false);
        self::assertResponseStatusCodeEquals($response, Response::HTTP_CREATED);
        self::assertResponseContentTypeEquals($response, $this->getResponseContentType());

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

        $response = $this->patch(['entity' => $entityType, 'id' => '100'], $data, [], false);
        self::assertResponseStatusCodeEquals($response, Response::HTTP_CREATED);
        self::assertResponseContentTypeEquals($response, $this->getResponseContentType());

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

        $response = $this->patch(
            ['entity' => $entityType, 'id' => $this->getCompositeId('new item', 100)],
            $data,
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_CREATED);
        self::assertResponseContentTypeEquals($response, $this->getResponseContentType());

        $expectedData = $data;
        unset($expectedData['data']['meta']);
        $this->assertResponseContains($expectedData, $response);

        $createdEntity = $this->getEntityWithCustomCompositeIdentifier('new item', 100);
        self::assertSame('New Item', $createdEntity->name);
    }

    public function testTryToForEntityWithAutoGeneratedId(): void
    {
        $entityType = $this->getEntityType(TestUniqueKeyIdentifier::class);

        $response = $this->patch(
            ['entity' => $entityType, 'id' => '<toString(test_unique_key_id1->id)>'],
            [
                'data' => [
                    'type'       => $entityType,
                    'id'         => '<toString(test_unique_key_id1->id)>',
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
                'detail' => 'The upsert operation is not supported for resources with auto-generated identifier value.',
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

        $response = $this->patch(['entity' => $entityType, 'id' => 'item 1'], $data);

        $expectedData = $data;
        unset($expectedData['data']['meta']);
        $this->assertResponseContains($expectedData, $response);

        $updatedEntity = $this->getEntityWithCustomIdentifier('item 1');
        self::assertSame('Updated Item 1', $updatedEntity->name);
    }

    public function testTryToByFieldsForEntityWithAutoGeneratedId(): void
    {
        $entityType = $this->getEntityType(TestUniqueKeyIdentifier::class);

        $response = $this->patch(
            ['entity' => $entityType, 'id' => '<toString(test_unique_key_id1->id)>'],
            [
                'data' => [
                    'type'       => $entityType,
                    'id'         => '<toString(test_unique_key_id1->id)>',
                    'meta'       => ['upsert' => ['key3']],
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
                'detail' => 'The upsert operation is not supported for resources with auto-generated identifier value.',
                'source' => ['pointer' => '/meta/upsert']
            ],
            $response
        );
    }

    public function testTryToByToOneAssociation(): void
    {
        $entityType = $this->getEntityType(TestCustomIdentifier::class);

        $response = $this->patch(
            ['entity' => $entityType, 'id' => 'item 1'],
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
                'detail' => 'Only the entity identifier can be used by the upsert operation to find an entity.',
                'source' => ['pointer' => '/meta/upsert']
            ],
            $response
        );
    }

    public function testTryToByToManyAssociation(): void
    {
        $entityType = $this->getEntityType(TestCustomIdentifier::class);

        $response = $this->patch(
            ['entity' => $entityType, 'id' => 'item 1'],
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
                'detail' => 'Only the entity identifier can be used by the upsert operation to find an entity.',
                'source' => ['pointer' => '/meta/upsert']
            ],
            $response
        );
    }

    public function testTryToByNotIdField(): void
    {
        $this->appendEntityConfig(
            TestUniqueKeyIdentifier::class,
            ['identifier_field_names' => ['key1']]
        );

        $entityType = $this->getEntityType(TestUniqueKeyIdentifier::class);

        $response = $this->patch(
            ['entity' => $entityType, 'id' => 'item 1'],
            [
                'data' => [
                    'type'       => $entityType,
                    'id'         => 'item 1',
                    'meta'       => ['upsert' => ['key7']],
                    'attributes' => [
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
                'title'  => 'value constraint',
                'detail' => 'Only the entity identifier can be used by the upsert operation to find an entity.',
                'source' => ['pointer' => '/meta/upsert']
            ],
            $response
        );
    }

    public function testTryToWhenEntityExistsAndAccessToItIsDenied(): void
    {
        $entityType = $this->getEntityType(TestCustomIntIdentifier::class);

        $response = $this->patch(
            ['entity' => $entityType, 'id' => '40'],
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
}
