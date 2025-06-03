<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiCustomization;

use Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadNestedAssociationData;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestCustomIdentifier as TestRelatedEntityWithCustomId;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDefaultAndNull as TestRelatedEntity;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEntityForNestedObjects as TestEntity;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class NestedAssociationTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadNestedAssociationData::class]);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetList(): void
    {
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);
        $relatedEntityWithCustomIdType = $this->getEntityType(TestRelatedEntityWithCustomId::class);

        $response = $this->cget(['entity' => $entityType]);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_1->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 1']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityType,
                                    'id' => '<toString(@test_related_entity_1->id)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_2->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 2']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => '<toString(@test_related_entity_with_custom_id_1->key)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_3->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 3']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityType,
                                    'id' => '<toString(@test_related_entity_2->id)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_4->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 4']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => '<toString(@test_related_entity_with_custom_id_2->key)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_5->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 5']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityType,
                                    'id' => '<toString(@test_related_entity_3->id)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_6->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 6']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => '<toString(@test_related_entity_with_custom_id_3->key)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_7->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 7']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => null
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithEqFilterByRelatedEntity(): void
    {
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);
        $relatedEntityId = $this->getReference('test_related_entity_1')->id;

        $response = $this->cget(
            ['entity' => $entityType],
            ['filter[relatedEntity][' . $relatedEntityType . ']' => $relatedEntityId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_1->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 1']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => ['type' => $relatedEntityType, 'id' => (string)$relatedEntityId]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithEqFilterByRelatedEntityWithCustomId(): void
    {
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityWithCustomIdType = $this->getEntityType(TestRelatedEntityWithCustomId::class);
        $relatedEntityWithCustomIdKey = $this->getReference('test_related_entity_with_custom_id_1')->key;

        $response = $this->cget(
            ['entity' => $entityType],
            ['filter[relatedEntity][' . $relatedEntityWithCustomIdType . ']' => $relatedEntityWithCustomIdKey]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_2->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 2']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => (string)$relatedEntityWithCustomIdKey
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithEqFilterByRelatedEntityBySeveralIds(): void
    {
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);
        $relatedEntity1Id = $this->getReference('test_related_entity_1')->id;
        $relatedEntity2Id = $this->getReference('test_related_entity_2')->id;

        $response = $this->cget(
            ['entity' => $entityType],
            ['filter[relatedEntity][' . $relatedEntityType . ']' => $relatedEntity1Id . ',' . $relatedEntity2Id]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_1->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 1']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => ['type' => $relatedEntityType, 'id' => (string)$relatedEntity1Id]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_3->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 3']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => ['type' => $relatedEntityType, 'id' => (string)$relatedEntity2Id]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithEqFilterByRelatedEntityWithCustomIdBySeveralIds(): void
    {
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityWithCustomIdType = $this->getEntityType(TestRelatedEntityWithCustomId::class);
        $relatedEntityWithCustomId1Key = $this->getReference('test_related_entity_with_custom_id_1')->key;
        $relatedEntityWithCustomId2Key = $this->getReference('test_related_entity_with_custom_id_2')->key;

        $response = $this->cget(
            ['entity' => $entityType],
            [
                'filter[relatedEntity][' . $relatedEntityWithCustomIdType . ']' =>
                    $relatedEntityWithCustomId1Key . ',' . $relatedEntityWithCustomId2Key
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_2->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 2']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => (string)$relatedEntityWithCustomId1Key
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_4->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 4']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => (string)$relatedEntityWithCustomId2Key
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithNeqFilterByRelatedEntity(): void
    {
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);
        $relatedEntityWithCustomIdType = $this->getEntityType(TestRelatedEntityWithCustomId::class);
        $relatedEntityId = $this->getReference('test_related_entity_1')->id;

        $response = $this->cget(
            ['entity' => $entityType],
            ['filter[relatedEntity][' . $relatedEntityType . '][neq]' => $relatedEntityId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_2->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 2']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => '<toString(@test_related_entity_with_custom_id_1->key)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_3->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 3']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityType,
                                    'id' => '<toString(@test_related_entity_2->id)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_4->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 4']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => '<toString(@test_related_entity_with_custom_id_2->key)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_5->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 5']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityType,
                                    'id' => '<toString(@test_related_entity_3->id)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_6->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 6']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => '<toString(@test_related_entity_with_custom_id_3->key)>'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithNeqFilterByRelatedEntityWithCustomId(): void
    {
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);
        $relatedEntityWithCustomIdType = $this->getEntityType(TestRelatedEntityWithCustomId::class);
        $relatedEntityWithCustomIdKey = $this->getReference('test_related_entity_with_custom_id_1')->key;

        $response = $this->cget(
            ['entity' => $entityType],
            ['filter[relatedEntity][' . $relatedEntityWithCustomIdType . '][neq]' => $relatedEntityWithCustomIdKey]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_1->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 1']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityType,
                                    'id' => '<toString(@test_related_entity_1->id)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_3->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 3']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityType,
                                    'id' => '<toString(@test_related_entity_2->id)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_4->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 4']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => '<toString(@test_related_entity_with_custom_id_2->key)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_5->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 5']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityType,
                                    'id' => '<toString(@test_related_entity_3->id)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_6->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 6']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => '<toString(@test_related_entity_with_custom_id_3->key)>'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithNeqFilterByRelatedEntityBySeveralIds(): void
    {
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);
        $relatedEntityWithCustomIdType = $this->getEntityType(TestRelatedEntityWithCustomId::class);
        $relatedEntity1Id = $this->getReference('test_related_entity_1')->id;
        $relatedEntity2Id = $this->getReference('test_related_entity_2')->id;

        $response = $this->cget(
            ['entity' => $entityType],
            ['filter[relatedEntity][' . $relatedEntityType . '][neq]' => $relatedEntity1Id . ',' . $relatedEntity2Id]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_2->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 2']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => '<toString(@test_related_entity_with_custom_id_1->key)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_4->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 4']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => '<toString(@test_related_entity_with_custom_id_2->key)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_5->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 5']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityType,
                                    'id' => '<toString(@test_related_entity_3->id)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_6->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 6']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => '<toString(@test_related_entity_with_custom_id_3->key)>'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithNeqFilterByRelatedEntityWithCustomIdBySeveralIds(): void
    {
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);
        $relatedEntityWithCustomIdType = $this->getEntityType(TestRelatedEntityWithCustomId::class);
        $relatedEntityWithCustomId1Key = $this->getReference('test_related_entity_with_custom_id_1')->key;
        $relatedEntityWithCustomId2Key = $this->getReference('test_related_entity_with_custom_id_2')->key;

        $response = $this->cget(
            ['entity' => $entityType],
            [
                'filter[relatedEntity][' . $relatedEntityWithCustomIdType . '][neq]' =>
                    $relatedEntityWithCustomId1Key . ',' . $relatedEntityWithCustomId2Key
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_1->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 1']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityType,
                                    'id' => '<toString(@test_related_entity_1->id)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_3->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 3']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityType,
                                    'id' => '<toString(@test_related_entity_2->id)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_5->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 5']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityType,
                                    'id' => '<toString(@test_related_entity_3->id)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_6->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 6']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => '<toString(@test_related_entity_with_custom_id_3->key)>'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithExistsFilterByRelatedEntity(): void
    {
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $response = $this->cget(
            ['entity' => $entityType],
            ['filter[relatedEntity][' . $relatedEntityType . '][exists]' => 'yes']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_1->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 1']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityType,
                                    'id' => '<toString(@test_related_entity_1->id)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_3->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 3']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityType,
                                    'id' => '<toString(@test_related_entity_2->id)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_5->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 5']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityType,
                                    'id' => '<toString(@test_related_entity_3->id)>'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithExistsFilterByRelatedEntityWithCustomId(): void
    {
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityWithCustomIdType = $this->getEntityType(TestRelatedEntityWithCustomId::class);

        $response = $this->cget(
            ['entity' => $entityType],
            ['filter[relatedEntity][' . $relatedEntityWithCustomIdType . '][exists]' => 'yes']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_2->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 2']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => '<toString(@test_related_entity_with_custom_id_1->key)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_4->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 4']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => '<toString(@test_related_entity_with_custom_id_2->key)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_6->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 6']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => '<toString(@test_related_entity_with_custom_id_3->key)>'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithNotExistsFilterByRelatedEntity(): void
    {
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);
        $relatedEntityWithCustomIdType = $this->getEntityType(TestRelatedEntityWithCustomId::class);

        $response = $this->cget(
            ['entity' => $entityType],
            ['filter[relatedEntity][' . $relatedEntityType . '][exists]' => 'no']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_2->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 2']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => '<toString(@test_related_entity_with_custom_id_1->key)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_4->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 4']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => '<toString(@test_related_entity_with_custom_id_2->key)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_6->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 6']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => '<toString(@test_related_entity_with_custom_id_3->key)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_7->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 7']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => null
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithNotExistsFilterByRelatedEntityWithCustomId(): void
    {
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);
        $relatedEntityWithCustomIdType = $this->getEntityType(TestRelatedEntityWithCustomId::class);

        $response = $this->cget(
            ['entity' => $entityType],
            ['filter[relatedEntity][' . $relatedEntityWithCustomIdType . '][exists]' => 'no']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_1->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 1']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityType,
                                    'id' => '<toString(@test_related_entity_1->id)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_3->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 3']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityType,
                                    'id' => '<toString(@test_related_entity_2->id)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_5->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 5']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityType,
                                    'id' => '<toString(@test_related_entity_3->id)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_7->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 7']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => null
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetListWithNeqOrNullFilterByRelatedEntity(): void
    {
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);
        $relatedEntityWithCustomIdType = $this->getEntityType(TestRelatedEntityWithCustomId::class);
        $relatedEntityId = $this->getReference('test_related_entity_1')->id;

        $response = $this->cget(
            ['entity' => $entityType],
            ['filter[relatedEntity][' . $relatedEntityType . '][neq_or_null]' => $relatedEntityId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_2->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 2']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => '<toString(@test_related_entity_with_custom_id_1->key)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_3->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 3']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityType,
                                    'id' => '<toString(@test_related_entity_2->id)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_4->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 4']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => '<toString(@test_related_entity_with_custom_id_2->key)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_5->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 5']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityType,
                                    'id' => '<toString(@test_related_entity_3->id)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_6->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 6']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => '<toString(@test_related_entity_with_custom_id_3->key)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_7->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 7']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => null
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetListWithNeqOrNullFilterByRelatedEntityWithCustomId(): void
    {
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);
        $relatedEntityWithCustomIdType = $this->getEntityType(TestRelatedEntityWithCustomId::class);
        $relatedEntityWithCustomIdKey = $this->getReference('test_related_entity_with_custom_id_1')->key;

        $response = $this->cget(
            ['entity' => $entityType],
            [
                'filter[relatedEntity][' . $relatedEntityWithCustomIdType . '][neq_or_null]' =>
                    $relatedEntityWithCustomIdKey
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_1->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 1']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityType,
                                    'id' => '<toString(@test_related_entity_1->id)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_3->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 3']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityType,
                                    'id' => '<toString(@test_related_entity_2->id)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_4->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 4']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => '<toString(@test_related_entity_with_custom_id_2->key)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_5->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 5']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityType,
                                    'id' => '<toString(@test_related_entity_3->id)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_6->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 6']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => '<toString(@test_related_entity_with_custom_id_3->key)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_7->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 7']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => null
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithNeqOrNullFilterByRelatedEntityBySeveralIds(): void
    {
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);
        $relatedEntityWithCustomIdType = $this->getEntityType(TestRelatedEntityWithCustomId::class);
        $relatedEntity1Id = $this->getReference('test_related_entity_1')->id;
        $relatedEntity2Id = $this->getReference('test_related_entity_2')->id;

        $response = $this->cget(
            ['entity' => $entityType],
            [
                'filter[relatedEntity][' . $relatedEntityType . '][neq_or_null]' =>
                    $relatedEntity1Id . ',' . $relatedEntity2Id
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_2->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 2']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => '<toString(@test_related_entity_with_custom_id_1->key)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_4->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 4']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => '<toString(@test_related_entity_with_custom_id_2->key)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_5->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 5']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityType,
                                    'id' => '<toString(@test_related_entity_3->id)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_6->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 6']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => '<toString(@test_related_entity_with_custom_id_3->key)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_7->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 7']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => null
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithNeqOrNullFilterByRelatedEntityWithCustomIdBySeveralIds(): void
    {
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);
        $relatedEntityWithCustomIdType = $this->getEntityType(TestRelatedEntityWithCustomId::class);
        $relatedEntityWithCustomId1Key = $this->getReference('test_related_entity_with_custom_id_1')->key;
        $relatedEntityWithCustomId2Key = $this->getReference('test_related_entity_with_custom_id_2')->key;

        $response = $this->cget(
            ['entity' => $entityType],
            [
                'filter[relatedEntity][' . $relatedEntityWithCustomIdType . '][neq_or_null]' =>
                    $relatedEntityWithCustomId1Key . ',' . $relatedEntityWithCustomId2Key
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_1->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 1']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityType,
                                    'id' => '<toString(@test_related_entity_1->id)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_3->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 3']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityType,
                                    'id' => '<toString(@test_related_entity_2->id)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_5->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 5']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityType,
                                    'id' => '<toString(@test_related_entity_3->id)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_6->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 6']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => [
                                    'type' => $relatedEntityWithCustomIdType,
                                    'id' => '<toString(@test_related_entity_with_custom_id_3->key)>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_7->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 7']
                        ],
                        'relationships' => [
                            'relatedEntity' => [
                                'data' => null
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredByRelatedEntityWhenRelatedEntityIsNotRequested(): void
    {
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);
        $relatedEntityId = $this->getReference('test_related_entity_1')->id;

        $response = $this->cget(
            ['entity' => $entityType],
            [
                'fields[' . $entityType . ']' => 'name',
                'filter[relatedEntity][' . $relatedEntityType . ']' => $relatedEntityId
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => $entityType,
                        'id' => '<toString(@test_entity_1->id)>',
                        'attributes' => [
                            'name' => ['firstName' => null, 'lastName' => 'Entity 1']
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseData = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('relationships', $responseData['data'][0]);
    }

    public function testGet(): void
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_1');
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $response = $this->get(['entity' => $entityType, 'id' => (string)$entity->getId()]);
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entity->getId(),
                    'attributes' => [
                        'name' => ['firstName' => null, 'lastName' => 'Entity 1']
                    ],
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id' => '<toString(@test_related_entity_1->id)>'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        $attributes = $responseContent['data']['attributes'];
        self::assertArrayNotHasKey('relatedClass', $attributes);
        self::assertArrayNotHasKey('relatedId', $attributes);
    }

    public function testGetForRelatedEntityWithCustomId(): void
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_2');
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntityWithCustomId::class);

        $response = $this->get(['entity' => $entityType, 'id' => (string)$entity->getId()]);
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entity->getId(),
                    'attributes' => [
                        'name' => ['firstName' => null, 'lastName' => 'Entity 2']
                    ],
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id' => '<toString(@test_related_entity_with_custom_id_1->key)>'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        $attributes = $responseContent['data']['attributes'];
        self::assertArrayNotHasKey('relatedClass', $attributes);
        self::assertArrayNotHasKey('relatedId', $attributes);
    }

    public function testGetWithTitleMetaProperty(): void
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_1');
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $response = $this->get(
            ['entity' => $entityType, 'id' => (string)$entity->getId()],
            ['meta' => 'title']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entity->getId(),
                    'meta' => [
                        'title' => 'Entity 1 ' . TestRelatedEntity::class
                    ],
                    'attributes' => [
                        'name' => ['firstName' => null, 'lastName' => 'Entity 1']
                    ],
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id' => '<toString(@test_related_entity_1->id)>'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        $attributes = $responseContent['data']['attributes'];
        self::assertArrayNotHasKey('relatedClass', $attributes);
        self::assertArrayNotHasKey('relatedId', $attributes);
    }

    public function testGetWithTitleMetaPropertyForRelatedEntityWithCustomId(): void
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_2');
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntityWithCustomId::class);

        $response = $this->get(
            ['entity' => $entityType, 'id' => (string)$entity->getId()],
            ['meta' => 'title']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entity->getId(),
                    'meta' => [
                        'title' => 'Entity 2 ' . TestRelatedEntityWithCustomId::class
                    ],
                    'attributes' => [
                        'name' => ['firstName' => null, 'lastName' => 'Entity 2']
                    ],
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id' => '<toString(@test_related_entity_with_custom_id_1->key)>'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        $attributes = $responseContent['data']['attributes'];
        self::assertArrayNotHasKey('relatedClass', $attributes);
        self::assertArrayNotHasKey('relatedId', $attributes);
    }

    public function testGetWithIncludeFilter(): void
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_1');
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $response = $this->get(
            ['entity' => $entityType, 'id' => (string)$entity->getId()],
            ['include' => 'relatedEntity']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entity->getId(),
                    'attributes' => [
                        'name' => ['firstName' => null, 'lastName' => 'Entity 1']
                    ],
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id' => '<toString(@test_related_entity_1->id)>'
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => $relatedEntityType,
                        'id' => '<toString(@test_related_entity_1->id)>',
                        'attributes' => [
                            'withNotBlank' => 'Related Entity 1'
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        $attributes = $responseContent['data']['attributes'];
        self::assertArrayNotHasKey('relatedClass', $attributes);
        self::assertArrayNotHasKey('relatedId', $attributes);
        foreach ($responseContent['included'] as $key => $item) {
            self::assertArrayNotHasKey('meta', $item, sprintf('included[%s]', $key));
        }
    }

    public function testGetWithIncludeFilterForRelatedEntityWithCustomId(): void
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_2');
        $entityType = $this->getEntityType(TestEntity::class);
        $relatedEntityType = $this->getEntityType(TestRelatedEntityWithCustomId::class);

        $response = $this->get(
            ['entity' => $entityType, 'id' => (string)$entity->getId()],
            ['include' => 'relatedEntity']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entity->getId(),
                    'attributes' => [
                        'name' => ['firstName' => null, 'lastName' => 'Entity 2']
                    ],
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id' => '<toString(@test_related_entity_with_custom_id_1->key)>'
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => $relatedEntityType,
                        'id' => '<toString(@test_related_entity_with_custom_id_1->key)>',
                        'attributes' => [
                            'name' => 'Related Entity 1'
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        $attributes = $responseContent['data']['attributes'];
        self::assertArrayNotHasKey('relatedClass', $attributes);
        self::assertArrayNotHasKey('relatedId', $attributes);
        foreach ($responseContent['included'] as $key => $item) {
            self::assertArrayNotHasKey('meta', $item, sprintf('included[%s]', $key));
        }
    }

    public function testCreate(): void
    {
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntity $relatedEntity1 */
        $relatedEntity1 = $this->getReference('test_related_entity_1');
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type' => $entityType,
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id' => (string)$relatedEntity1->id
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id' => (string)$relatedEntity1->id
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );

        // test that the data was created
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, (int)$this->getResourceId($response));
        self::assertEquals(TestRelatedEntity::class, $entity->getRelatedClass());
        self::assertSame($relatedEntity1->id, $entity->getRelatedId());
    }

    public function testCreateForRelatedEntityWithCustomId(): void
    {
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntityWithCustomId $relatedEntity1 */
        $relatedEntity1 = $this->getReference('test_related_entity_with_custom_id_1');
        $relatedEntityType = $this->getEntityType(TestRelatedEntityWithCustomId::class);

        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type' => $entityType,
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id' => (string)$relatedEntity1->key
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id' => (string)$relatedEntity1->key
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );

        // test that the data was created
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, (int)$this->getResourceId($response));
        self::assertEquals(TestRelatedEntityWithCustomId::class, $entity->getRelatedClass());
        self::assertSame($relatedEntity1->autoincrementKey, $entity->getRelatedId());
    }

    public function testCreateWithoutNestedAssociationData(): void
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type' => $entityType
                ]
            ]
        );

        $responseContent = self::jsonToArray($response->getContent());
        self::assertNull(
            $responseContent['data']['relationships']['relatedEntity']['data']
        );

        // test that the data was created
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, (int)$responseContent['data']['id']);
        self::assertNull($entity->getRelatedClass());
        self::assertNull($entity->getRelatedId());
    }

    public function testCreateWithoutNestedAssociationDataForRelatedEntityWithCustomId(): void
    {
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type' => $entityType
                ]
            ]
        );

        $responseContent = self::jsonToArray($response->getContent());
        self::assertNull(
            $responseContent['data']['relationships']['relatedEntity']['data']
        );

        // test that the data was created
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, (int)$responseContent['data']['id']);
        self::assertNull($entity->getRelatedClass());
        self::assertNull($entity->getRelatedId());
    }

    public function testUpdate(): void
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_1');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntity $relatedEntity2 */
        $relatedEntity2 = $this->getReference('test_related_entity_2');
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entity->getId()],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entity->getId(),
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id' => (string)$relatedEntity2->id
                            ]
                        ]
                    ]
                ]
            ]
        );

        $responseContent = self::jsonToArray($response->getContent());
        self::assertEquals(
            [
                'type' => $relatedEntityType,
                'id' => (string)$relatedEntity2->id
            ],
            $responseContent['data']['relationships']['relatedEntity']['data']
        );

        // test that the data was updated
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertEquals(TestRelatedEntity::class, $entity->getRelatedClass());
        self::assertSame($relatedEntity2->id, $entity->getRelatedId());
    }

    public function testUpdateForRelatedEntityWithCustomId(): void
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_2');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntityWithCustomId $relatedEntity2 */
        $relatedEntity2 = $this->getReference('test_related_entity_with_custom_id_2');
        $relatedEntityType = $this->getEntityType(TestRelatedEntityWithCustomId::class);

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entity->getId()],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entity->getId(),
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => [
                                'type' => $relatedEntityType,
                                'id' => (string)$relatedEntity2->key
                            ]
                        ]
                    ]
                ]
            ]
        );

        $responseContent = self::jsonToArray($response->getContent());
        self::assertEquals(
            [
                'type' => $relatedEntityType,
                'id' => (string)$relatedEntity2->key
            ],
            $responseContent['data']['relationships']['relatedEntity']['data']
        );

        // test that the data was updated
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertEquals(TestRelatedEntityWithCustomId::class, $entity->getRelatedClass());
        self::assertSame($relatedEntity2->autoincrementKey, $entity->getRelatedId());
    }

    public function testUpdateToNull(): void
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_1');
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entity->getId()],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entity->getId(),
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => null
                        ]
                    ]
                ]
            ]
        );

        $responseContent = self::jsonToArray($response->getContent());
        self::assertNull(
            $responseContent['data']['relationships']['relatedEntity']['data']
        );

        // test that the data was updated
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertNull($entity->getRelatedClass());
        self::assertNull($entity->getRelatedId());
    }

    public function testUpdateToNullForRelatedEntityWithCustomId(): void
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_2');
        $entityType = $this->getEntityType(TestEntity::class);

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entity->getId()],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entity->getId(),
                    'relationships' => [
                        'relatedEntity' => [
                            'data' => null
                        ]
                    ]
                ]
            ]
        );

        $responseContent = self::jsonToArray($response->getContent());
        self::assertNull(
            $responseContent['data']['relationships']['relatedEntity']['data']
        );

        // test that the data was updated
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertNull($entity->getRelatedClass());
        self::assertNull($entity->getRelatedId());
    }

    public function testGetSubresource(): void
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_1');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntity $relatedEntity */
        $relatedEntity = $this->getReference('test_related_entity_1');
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $response = $this->getSubresource([
            'entity' => $entityType,
            'id' => (string)$entity->getId(),
            'association' => 'relatedEntity'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $relatedEntityType,
                    'id' => (string)$relatedEntity->id,
                    'attributes' => [
                        'withNotBlank' => 'Related Entity 1'
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('meta', $responseContent['data']);
    }

    public function testGetSubresourceForRelatedEntityWithCustomId(): void
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_2');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntityWithCustomId $relatedEntity */
        $relatedEntity = $this->getReference('test_related_entity_with_custom_id_1');
        $relatedEntityType = $this->getEntityType(TestRelatedEntityWithCustomId::class);

        $response = $this->getSubresource([
            'entity' => $entityType,
            'id' => (string)$entity->getId(),
            'association' => 'relatedEntity'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $relatedEntityType,
                    'id' => (string)$relatedEntity->key,
                    'attributes' => [
                        'name' => 'Related Entity 1'
                    ]
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('meta', $responseContent['data']);
    }

    public function testGetSubresourceWithTitle(): void
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_1');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntity $relatedEntity */
        $relatedEntity = $this->getReference('test_related_entity_1');
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $response = $this->getSubresource([
            'entity' => $entityType,
            'id' => (string)$entity->getId(),
            'association' => 'relatedEntity',
            'meta' => 'title'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $relatedEntityType,
                    'id' => (string)$relatedEntity->id,
                    'meta' => [
                        'title' => 'default default_NotBlank default_NotNull Related Entity 1'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceWithTitleForRelatedEntityWithCustomId(): void
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_2');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntityWithCustomId $relatedEntity */
        $relatedEntity = $this->getReference('test_related_entity_with_custom_id_1');
        $relatedEntityType = $this->getEntityType(TestRelatedEntityWithCustomId::class);

        $response = $this->getSubresource([
            'entity' => $entityType,
            'id' => (string)$entity->getId(),
            'association' => 'relatedEntity',
            'meta' => 'title'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $relatedEntityType,
                    'id' => (string)$relatedEntity->key,
                    'meta' => [
                        'title' => 'key1 Related Entity 1'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationship(): void
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_1');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntity $relatedEntity */
        $relatedEntity = $this->getReference('test_related_entity_1');
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $response = $this->getRelationship([
            'entity' => $entityType,
            'id' => (string)$entity->getId(),
            'association' => 'relatedEntity'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $relatedEntityType,
                    'id' => (string)$relatedEntity->id
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('meta', $responseContent['data']);
        self::assertArrayNotHasKey('attributes', $responseContent['data']);
        self::assertArrayNotHasKey('relationships', $responseContent['data']);
    }

    public function testGetRelationshipForRelatedEntityWithCustomId(): void
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_2');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntityWithCustomId $relatedEntity */
        $relatedEntity = $this->getReference('test_related_entity_with_custom_id_1');
        $relatedEntityType = $this->getEntityType(TestRelatedEntityWithCustomId::class);

        $response = $this->getRelationship([
            'entity' => $entityType,
            'id' => (string)$entity->getId(),
            'association' => 'relatedEntity'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $relatedEntityType,
                    'id' => (string)$relatedEntity->key
                ]
            ],
            $response
        );
        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('meta', $responseContent['data']);
        self::assertArrayNotHasKey('attributes', $responseContent['data']);
        self::assertArrayNotHasKey('relationships', $responseContent['data']);
    }

    public function testUpdateRelationship(): void
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_1');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntity $relatedEntity2 */
        $relatedEntity2 = $this->getReference('test_related_entity_2');
        $relatedEntityType = $this->getEntityType(TestRelatedEntity::class);

        $this->patchRelationship(
            ['entity' => $entityType, 'id' => (string)$entity->getId(), 'association' => 'relatedEntity'],
            [
                'data' => [
                    'type' => $relatedEntityType,
                    'id' => (string)$relatedEntity2->id
                ]
            ]
        );

        // test that the data was updated
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertEquals(TestRelatedEntity::class, $entity->getRelatedClass());
        self::assertSame($relatedEntity2->id, $entity->getRelatedId());
    }

    public function testUpdateRelationshipForRelatedEntityWithCustomId(): void
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_2');
        $entityType = $this->getEntityType(TestEntity::class);

        /** @var TestRelatedEntityWithCustomId $relatedEntity2 */
        $relatedEntity2 = $this->getReference('test_related_entity_with_custom_id_2');
        $relatedEntityType = $this->getEntityType(TestRelatedEntityWithCustomId::class);

        $this->patchRelationship(
            ['entity' => $entityType, 'id' => (string)$entity->getId(), 'association' => 'relatedEntity'],
            [
                'data' => [
                    'type' => $relatedEntityType,
                    'id' => (string)$relatedEntity2->key
                ]
            ]
        );

        // test that the data was updated
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertEquals(TestRelatedEntityWithCustomId::class, $entity->getRelatedClass());
        self::assertSame($relatedEntity2->autoincrementKey, $entity->getRelatedId());
    }

    public function testUpdateRelationshipToNull(): void
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_1');
        $entityType = $this->getEntityType(TestEntity::class);

        $this->patchRelationship(
            ['entity' => $entityType, 'id' => (string)$entity->getId(), 'association' => 'relatedEntity'],
            ['data' => null]
        );

        // test that the data was updated
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertNull($entity->getRelatedClass());
        self::assertNull($entity->getRelatedId());
    }

    public function testUpdateRelationshipToNullForRelatedEntityWithCustomId(): void
    {
        /** @var TestEntity $entity */
        $entity = $this->getReference('test_entity_2');
        $entityType = $this->getEntityType(TestEntity::class);

        $this->patchRelationship(
            ['entity' => $entityType, 'id' => (string)$entity->getId(), 'association' => 'relatedEntity'],
            ['data' => null]
        );

        // test that the data was updated
        $this->getEntityManager()->clear();
        $entity = $this->getEntityManager()->find(TestEntity::class, $entity->getId());
        self::assertNull($entity->getRelatedClass());
        self::assertNull($entity->getRelatedId());
    }
}
