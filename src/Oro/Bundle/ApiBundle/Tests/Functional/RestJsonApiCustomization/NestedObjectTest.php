<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiCustomization;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEntityForNestedObjects as TestEntity;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @dbIsolationPerTest
 */
class NestedObjectTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures(['@OroApiBundle/Tests/Functional/DataFixtures/nested_object.yml']);
    }

    public function testGet()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->get(['entity' => $entityType, 'id' => (string)$entity->getId()]);

        $result = self::jsonToArray($response->getContent());
        self::assertEquals((string)$entity->getId(), $result['data']['id']);
        self::assertEquals(
            [
                'firstName'   => 'first name',
                'lastName'    => 'last name',
                'contactedAt' => '2010-11-01T10:12:13Z'
            ],
            $result['data']['attributes']['name']
        );
        self::assertEquals(
            [
                'value' => 'middle name'
            ],
            $result['data']['attributes']['middle']
        );
        self::assertEquals(
            [
                'value' => 'name prefix'
            ],
            $result['data']['attributes']['prefix']
        );
        self::assertEquals(
            [
                'value' => 'name suffix'
            ],
            $result['data']['attributes']['suffix']
        );
    }

    public function testGetWithEmptyNestedObjectData()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_empty_entity');
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->get(['entity' => $entityType, 'id' => (string)$entity->getId()]);

        $result = self::jsonToArray($response->getContent());
        self::assertEquals((string)$entity->getId(), $result['data']['id']);
        self::assertNull($result['data']['attributes']['name']);
        self::assertNull($result['data']['attributes']['middle']);
        self::assertNull($result['data']['attributes']['prefix']);
        self::assertNull($result['data']['attributes']['suffix']);
    }

    public function testGetWithoutNestedObjectData()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_no_data_entity');
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->get(['entity' => $entityType, 'id' => (string)$entity->getId()]);

        $result = self::jsonToArray($response->getContent());
        self::assertEquals((string)$entity->getId(), $result['data']['id']);
        self::assertNull($result['data']['attributes']['name']);
        self::assertNull($result['data']['attributes']['middle']);
        self::assertNull($result['data']['attributes']['prefix']);
        self::assertNull($result['data']['attributes']['suffix']);
    }

    public function testCreate()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'attributes' => [
                    'name'   => [
                        'firstName'   => 'first name',
                        'lastName'    => 'last name',
                        'contactedAt' => '2010-11-01T10:12:13Z'
                    ],
                    'middle' => [
                        'value' => 'middle name'
                    ],
                    'prefix' => [
                        'value' => 'name prefix'
                    ],
                    'suffix' => [
                        'value' => 'name suffix'
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $result = self::jsonToArray($response->getContent());
        self::assertEquals(
            [
                'firstName'   => 'first name',
                'lastName'    => 'last name',
                'contactedAt' => '2010-11-01T10:12:13Z'
            ],
            $result['data']['attributes']['name']
        );
        self::assertNull($result['data']['attributes']['middle']);
        self::assertEquals(
            [
                'value' => 'name prefix'
            ],
            $result['data']['attributes']['prefix']
        );
        self::assertNull($result['data']['attributes']['suffix']);

        // test that the data was created
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, (int)$result['data']['id']);
        self::assertEquals('first name', $entity->getFirstName());
        self::assertEquals('last name', $entity->getLastName());
        self::assertNull($entity->getMiddleName());
        self::assertEquals('name prefix', $entity->getNamePrefix());
        self::assertNull($entity->getNameSuffix());
    }

    public function testCreateWithEmptyNestedObjectData()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'attributes' => [
                    'name'   => [
                        'firstName'   => '',
                        'lastName'    => '',
                        'contactedAt' => null
                    ],
                    'middle' => [
                        'value' => ''
                    ],
                    'prefix' => [
                        'value' => ''
                    ],
                    'suffix' => [
                        'value' => ''
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $result = self::jsonToArray($response->getContent());
        self::assertNull($result['data']['attributes']['name']);
        self::assertNull($result['data']['attributes']['middle']);
        self::assertNull($result['data']['attributes']['prefix']);
        self::assertNull($result['data']['attributes']['suffix']);

        // test that the data was created
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, (int)$result['data']['id']);
        self::assertNull($entity->getFirstName());
        self::assertNull($entity->getLastName());
        self::assertNull($entity->getMiddleName());
        self::assertNull($entity->getNamePrefix());
        self::assertNull($entity->getNameSuffix());
    }

    public function testCreateWithoutNestedObjectData()
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $data = [
            'data' => [
                'type' => $entityType
            ]
        ];

        $response = $this->post(['entity' => $entityType], $data);

        $result = self::jsonToArray($response->getContent());
        self::assertNull($result['data']['attributes']['name']);
        self::assertNull($result['data']['attributes']['middle']);
        self::assertNull($result['data']['attributes']['prefix']);
        self::assertNull($result['data']['attributes']['suffix']);

        // test that the data was created
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, (int)$result['data']['id']);
        self::assertNull($entity->getFirstName());
        self::assertNull($entity->getLastName());
        self::assertNull($entity->getMiddleName());
        self::assertNull($entity->getNamePrefix());
        self::assertNull($entity->getNameSuffix());
    }

    public function testUpdate()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => (string)$entity->getId(),
                'attributes' => [
                    'name'   => [
                        'firstName'   => 'new first name',
                        'lastName'    => 'new last name',
                        'contactedAt' => '2011-12-01T10:12:13Z'
                    ],
                    'middle' => [
                        'value' => 'new middle name'
                    ],
                    'prefix' => [
                        'value' => 'new name prefix'
                    ],
                    'suffix' => [
                        'value' => 'new name suffix'
                    ]
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entity->getId()],
            $data
        );

        $result = self::jsonToArray($response->getContent());
        self::assertEquals(
            [
                'firstName'   => 'new first name',
                'lastName'    => 'new last name',
                'contactedAt' => '2011-12-01T10:12:13Z'
            ],
            $result['data']['attributes']['name']
        );
        self::assertEquals(
            [
                'value' => 'middle name'
            ],
            $result['data']['attributes']['middle']
        );
        self::assertEquals(
            [
                'value' => 'new name prefix'
            ],
            $result['data']['attributes']['prefix']
        );
        self::assertEquals(
            [
                'value' => 'name suffix'
            ],
            $result['data']['attributes']['suffix']
        );

        // test that the data was updated
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertEquals('new first name', $entity->getFirstName());
        self::assertEquals('new last name', $entity->getLastName());
        self::assertEquals('middle name', $entity->getMiddleName());
        self::assertEquals('new name prefix', $entity->getNamePrefix());
        self::assertEquals('name suffix', $entity->getNameSuffix());
    }

    public function testUpdateToEmptyData()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => (string)$entity->getId(),
                'attributes' => [
                    'name'   => [
                        'firstName'   => '',
                        'lastName'    => '',
                        'contactedAt' => null
                    ],
                    'middle' => [
                        'value' => ''
                    ],
                    'prefix' => [
                        'value' => ''
                    ],
                    'suffix' => [
                        'value' => ''
                    ]
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entity->getId()],
            $data
        );

        $result = self::jsonToArray($response->getContent());
        self::assertNull($result['data']['attributes']['name']);
        self::assertEquals(
            [
                'value' => 'middle name'
            ],
            $result['data']['attributes']['middle']
        );
        self::assertNull($result['data']['attributes']['prefix']);
        self::assertEquals(
            [
                'value' => 'name suffix'
            ],
            $result['data']['attributes']['suffix']
        );

        // test that the data was updated
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertNull($entity->getFirstName());
        self::assertNull($entity->getLastName());
        self::assertEquals('middle name', $entity->getMiddleName());
        self::assertNull($entity->getNamePrefix());
        self::assertEquals('name suffix', $entity->getNameSuffix());
    }

    public function testUpdateToNull()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => (string)$entity->getId(),
                'attributes' => [
                    'name'   => null,
                    'middle' => null,
                    'prefix' => null,
                    'suffix' => null
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entity->getId()],
            $data
        );

        $result = self::jsonToArray($response->getContent());
        self::assertNull($result['data']['attributes']['name']);
        self::assertEquals(
            [
                'value' => 'middle name'
            ],
            $result['data']['attributes']['middle']
        );
        self::assertNull($result['data']['attributes']['prefix']);
        self::assertEquals(
            [
                'value' => 'name suffix'
            ],
            $result['data']['attributes']['suffix']
        );

        // test that the data was updated
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertNull($entity->getFirstName());
        self::assertNull($entity->getLastName());
        self::assertEquals('middle name', $entity->getMiddleName());
        self::assertNull($entity->getNamePrefix());
        self::assertEquals('name suffix', $entity->getNameSuffix());
    }

    public function testUpdateWithEmptyArray()
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity');
        $entityType = $this->getEntityType(TestEntity::class);

        $data = [
            'data' => [
                'type'       => $entityType,
                'id'         => (string)$entity->getId(),
                'attributes' => [
                    'name'   => [],
                    'middle' => [],
                    'prefix' => [],
                    'suffix' => []
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entity->getId()],
            $data
        );

        $result = self::jsonToArray($response->getContent());
        self::assertEquals(
            [
                'firstName'   => 'first name',
                'lastName'    => 'last name',
                'contactedAt' => '2010-11-01T10:12:13Z'
            ],
            $result['data']['attributes']['name']
        );
        self::assertEquals(
            [
                'value' => 'middle name'
            ],
            $result['data']['attributes']['middle']
        );
        self::assertEquals(
            [
                'value' => 'name prefix'
            ],
            $result['data']['attributes']['prefix']
        );
        self::assertEquals(
            [
                'value' => 'name suffix'
            ],
            $result['data']['attributes']['suffix']
        );

        // test that the data was updated
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertEquals('first name', $entity->getFirstName());
        self::assertEquals('last name', $entity->getLastName());
        self::assertEquals('middle name', $entity->getMiddleName());
        self::assertEquals('name prefix', $entity->getNamePrefix());
        self::assertEquals('name suffix', $entity->getNameSuffix());
    }
}
