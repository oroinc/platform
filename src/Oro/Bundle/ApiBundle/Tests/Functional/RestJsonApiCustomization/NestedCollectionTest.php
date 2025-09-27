<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiCustomization;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestCustomIdentifier as TestLinkEntity;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEntityForNestedObjects as TestEntity;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @dbIsolationPerTest
 */
class NestedCollectionTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/nested_collection.yml'
        ]);
    }

    public function testGet()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->get(['entity' => $entityType, 'id' => (string)$entity->getId()]);

        $result = self::jsonToArray($response->getContent());
        self::assertEquals((string)$entity->getId(), $result['data']['id']);
        self::assertArrayContains(
            [
                ['firstName' => 'Item 1', 'lastName' => 'item1'],
                ['firstName' => 'Item 2', 'lastName' => 'item2']
            ],
            $result['data']['attributes']['links']
        );
        self::assertCount(2, $result['data']['attributes']['links']);
    }

    public function testCreate()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $data = [
            'data' => [
                'type' => $entityType,
                'attributes' => [
                    'formatted' => ['value' => '-'],
                    'links' => [
                        ['firstName' => 'New Item 1', 'lastName' => 'new_item1'],
                        ['firstName' => 'New Item 2', 'lastName' => 'new_item2']
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $result = self::jsonToArray($response->getContent());
        self::assertArrayContains(
            [
                ['firstName' => 'New Item 1', 'lastName' => 'new_item1'],
                ['firstName' => 'New Item 2', 'lastName' => 'new_item2']
            ],
            $result['data']['attributes']['links']
        );
        self::assertCount(2, $result['data']['attributes']['links']);

        // test that the data was created
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, (int)$result['data']['id']);
        /** @var TestLinkEntity[] $links */
        $links = $entity->getLinks();
        self::assertCount(2, $links);
        self::assertEquals('New Item 1', $links[0]->name, '$links[0]->firstName');
        self::assertEquals('new_item1', $links[0]->key, '$links[0]->lastName');
        self::assertEquals('New Item 2', $links[1]->name, '$links[1]->firstName');
        self::assertEquals('new_item2', $links[1]->key, '$links[1]->lastName');
    }

    public function testCreateWithoutNestedCollectionData()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $data = [
            'data' => [
                'type' => $entityType,
                'attributes' => ['formatted' => ['value' => '-']]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $result = self::jsonToArray($response->getContent());
        self::assertSame(
            [],
            $result['data']['attributes']['links']
        );

        // test that the data was created
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, (int)$result['data']['id']);
        self::assertCount(0, $entity->getLinks());
    }

    public function testUpdate()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);

        $data = [
            'data' => [
                'type' => $entityType,
                'id' => (string)$entity->getId(),
                'attributes' => [
                    'links' => [
                        ['firstName' => 'New Item 2', 'lastName' => 'new_item2'],
                        ['firstName' => 'New Item 3', 'lastName' => 'new_item3']
                    ]
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entity->getId()],
            $data
        );

        $result = self::jsonToArray($response->getContent());
        self::assertArrayContains(
            [
                ['firstName' => 'New Item 2', 'lastName' => 'new_item2'],
                ['firstName' => 'New Item 3', 'lastName' => 'new_item3']
            ],
            $result['data']['attributes']['links']
        );
        self::assertCount(2, $result['data']['attributes']['links']);

        // test that the data was updated
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        /** @var TestLinkEntity[] $links */
        $links = $entity->getLinks();
        self::assertCount(2, $links);
        self::assertEquals('New Item 2', $links[0]->name, '$links[0]->firstName');
        self::assertEquals('new_item2', $links[0]->key, '$links[0]->lastName');
        self::assertEquals('New Item 3', $links[1]->name, '$links[1]->firstName');
        self::assertEquals('new_item3', $links[1]->key, '$links[1]->lastName');
    }

    public function testUpdateToEmpty()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);

        $data = [
            'data' => [
                'type' => $entityType,
                'id' => (string)$entity->getId(),
                'attributes' => [
                    'links' => []
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entity->getId()],
            $data
        );

        $result = self::jsonToArray($response->getContent());
        self::assertSame(
            [],
            $result['data']['attributes']['links']
        );

        // test that the data was updated
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertCount(0, $entity->getLinks());
    }
}
