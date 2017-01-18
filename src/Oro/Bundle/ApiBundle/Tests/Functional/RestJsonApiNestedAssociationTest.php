<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadNestedAssociationData;
use Oro\Bundle\TestFrameworkBundle\Entity\TestDefaultAndNull as TestRelatedEntity;
use Oro\Bundle\TestFrameworkBundle\Entity\TestEntityForNestedObjects as TestEntity;

/**
 * @dbIsolationPerTest
 */
class RestJsonApiNestedAssociationTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([LoadNestedAssociationData::class]);
    }

    public function testGet()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntity $relatedEntity */
        $relatedEntity = $this->getReference('test_related_entity1');
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $response = $this->request(
            'GET',
            $this->getUrl('oro_rest_api_get', ['entity' => $entityType, 'id' => (string)$entity->getId()])
        );

        self::assertResponseStatusCodeEquals($response, 200);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        $result = self::jsonToArray($response->getContent());
        self::assertEquals((string)$entity->getId(), $result['data']['id']);
        self::assertEquals(
            [
                'type' => $relatedEntityType,
                'id'   => (string)$relatedEntity->id
            ],
            $result['data']['relationships']['relatedEntity']['data']
        );
    }

    public function testCreate()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntity $relatedEntity1 */
        $relatedEntity1 = $this->getReference('test_related_entity1');
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $data = [
            'data' => [
                'type'          => $entityType,
                'relationships' => [
                    'relatedEntity' => [
                        'data' => [
                            'type' => $relatedEntityType,
                            'id'   => (string)$relatedEntity1->id
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post', ['entity' => $entityType]),
            $data
        );

        self::assertResponseStatusCodeEquals($response, 201);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        $result = self::jsonToArray($response->getContent());
        self::assertEquals(
            [
                'type' => $relatedEntityType,
                'id'   => $relatedEntity1->id
            ],
            $result['data']['relationships']['relatedEntity']['data']
        );

        // test that the data was created
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, (int)$result['data']['id']);
        self::assertEquals(TestRelatedEntity::class, $entity->getRelatedClass());
        self::assertSame($relatedEntity1->id, $entity->getRelatedId());
    }

    public function testCreateWithoutNestedAssociationData()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $data = [
            'data' => [
                'type' => $entityType
            ]
        ];

        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post', ['entity' => $entityType]),
            $data
        );

        self::assertResponseStatusCodeEquals($response, 201);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        $result = self::jsonToArray($response->getContent());
        self::assertNull(
            $result['data']['relationships']['relatedEntity']['data']
        );

        // test that the data was created
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, (int)$result['data']['id']);
        self::assertNull($entity->getRelatedClass());
        self::assertNull($entity->getRelatedId());
    }

    public function testUpdate()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntity $relatedEntity2 */
        $relatedEntity2 = $this->getReference('test_related_entity2');
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $data = [
            'data' => [
                'type'          => $entityType,
                'id'            => (string)$entity->getId(),
                'relationships' => [
                    'relatedEntity' => [
                        'data' => [
                            'type' => $relatedEntityType,
                            'id'   => (string)$relatedEntity2->id
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->request(
            'PATCH',
            $this->getUrl('oro_rest_api_patch', ['entity' => $entityType, 'id' => (string)$entity->getId()]),
            $data
        );

        self::assertResponseStatusCodeEquals($response, 200);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        $result = self::jsonToArray($response->getContent());
        self::assertEquals(
            [
                'type' => $relatedEntityType,
                'id'   => (string)$relatedEntity2->id
            ],
            $result['data']['relationships']['relatedEntity']['data']
        );

        // test that the data was updated
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertEquals(TestRelatedEntity::class, $entity->getRelatedClass());
        self::assertSame($relatedEntity2->id, $entity->getRelatedId());
    }

    public function testUpdateToNull()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);

        $data = [
            'data' => [
                'type'          => $entityType,
                'id'            => (string)$entity->getId(),
                'relationships' => [
                    'relatedEntity' => [
                        'data' => null
                    ]
                ]
            ]
        ];

        $response = $this->request(
            'PATCH',
            $this->getUrl('oro_rest_api_patch', ['entity' => $entityType, 'id' => (string)$entity->getId()]),
            $data
        );

        self::assertResponseStatusCodeEquals($response, 200);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        $result = self::jsonToArray($response->getContent());
        self::assertNull(
            $result['data']['relationships']['relatedEntity']['data']
        );

        // test that the data was updated
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertNull($entity->getRelatedClass());
        self::assertNull($entity->getRelatedId());
    }

    public function testGetSubresource()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntity $relatedEntity */
        $relatedEntity = $this->getReference('test_related_entity1');
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $response = $this->request(
            'GET',
            $this->getUrl(
                'oro_rest_api_get_subresource',
                [
                    'entity'      => $entityType,
                    'id'          => (string)$entity->getId(),
                    'association' => 'relatedEntity'
                ]
            )
        );

        self::assertResponseStatusCodeEquals($response, 200);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        $result = self::jsonToArray($response->getContent());
        self::assertEquals(
            [
                'type' => $relatedEntityType,
                'id'   => (string)$relatedEntity->id
            ],
            $result['data']
        );
    }

    public function testGetSubresourceWithTitle()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntity $relatedEntity */
        $relatedEntity = $this->getReference('test_related_entity1');
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $response = $this->request(
            'GET',
            $this->getUrl(
                'oro_rest_api_get_subresource',
                [
                    'entity'      => $entityType,
                    'id'          => (string)$entity->getId(),
                    'association' => 'relatedEntity',
                    'meta'        => 'title'
                ]
            )
        );

        self::assertResponseStatusCodeEquals($response, 200);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        $result = self::jsonToArray($response->getContent());
        self::assertEquals(
            [
                'type' => $relatedEntityType,
                'id'   => (string)$relatedEntity->id,
                'meta' => [
                    'title' => 'default default_NotBlank default_NotNull'
                ]
            ],
            $result['data']
        );
    }

    public function testGetRelationship()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntity $relatedEntity */
        $relatedEntity = $this->getReference('test_related_entity1');
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $response = $this->request(
            'GET',
            $this->getUrl(
                'oro_rest_api_get_relationship',
                [
                    'entity'      => $entityType,
                    'id'          => (string)$entity->getId(),
                    'association' => 'relatedEntity'
                ]
            )
        );

        self::assertResponseStatusCodeEquals($response, 200);
        self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);
        $result = self::jsonToArray($response->getContent());
        self::assertEquals(
            [
                'type' => $relatedEntityType,
                'id'   => (string)$relatedEntity->id
            ],
            $result['data']
        );
    }

    public function testUpdateRelationship()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntity $relatedEntity2 */
        $relatedEntity2 = $this->getReference('test_related_entity2');
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $data = [
            'data' => [
                'type' => $relatedEntityType,
                'id'   => (string)$relatedEntity2->id
            ]
        ];

        $response = $this->request(
            'PATCH',
            $this->getUrl(
                'oro_rest_api_patch_relationship',
                [
                    'entity'      => $entityType,
                    'id'          => (string)$entity->getId(),
                    'association' => 'relatedEntity'
                ]
            ),
            $data
        );

        self::assertResponseStatusCodeEquals($response, 204);

        // test that the data was updated
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertEquals(TestRelatedEntity::class, $entity->getRelatedClass());
        self::assertSame($relatedEntity2->id, $entity->getRelatedId());
    }

    public function testUpdateRelationshipToNull()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);

        $data = [
            'data' => null
        ];

        $response = $this->request(
            'PATCH',
            $this->getUrl(
                'oro_rest_api_patch_relationship',
                [
                    'entity'      => $entityType,
                    'id'          => (string)$entity->getId(),
                    'association' => 'relatedEntity'
                ]
            ),
            $data
        );

        self::assertResponseStatusCodeEquals($response, 204);

        // test that the data was updated
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertNull($entity->getRelatedClass());
        self::assertNull($entity->getRelatedId());
    }
}
