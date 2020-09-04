<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UnidirectionalAssociationsTest extends RestJsonApiTestCase
{
    protected const ENTITY_CLASS        = 'Extend\Entity\TestApiE2';
    protected const TARGET_ENTITY_CLASS = 'Extend\Entity\TestApiE1';

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/unidirectional_associations.yml'
        ]);
    }

    /**
     * @param string $entityClass
     * @param int    $entityId
     *
     * @return object|null
     */
    protected function getEntity($entityClass, $entityId)
    {
        return $this->getEntityManager()->find($entityClass, $entityId);
    }

    /**
     * @param array    $expectedContent
     * @param string   $relationshipName
     * @param Response $response
     */
    private function assertRelationshipEquals($expectedContent, $relationshipName, Response $response)
    {
        $this->assertResponseContains($expectedContent, $response);

        $content = self::jsonToArray($response->getContent());
        self::assertArrayHasKey('data', $content);
        self::assertArrayHasKey('relationships', $content['data']);
        self::assertArrayHasKey($relationshipName, $content['data']['relationships']);
        self::assertCount(
            count($expectedContent['data']['relationships'][$relationshipName]),
            $content['data']['relationships'][$relationshipName]
        );
    }

    /**
     * @param int[]      $expectedEntityIds
     * @param Collection $association
     */
    private function assertToManyAssociation(array $expectedEntityIds, Collection $association)
    {
        $associationIds = \array_map(
            function ($entity) {
                return $entity->getId();
            },
            $association->toArray()
        );
        \sort($associationIds);
        \sort($expectedEntityIds);
        self::assertEquals($expectedEntityIds, $associationIds);
    }

    public function testGetListForEntityWithTargetSideOfUnidirectionalAssociations()
    {
        $entityType = $this->getEntityType(static::ENTITY_CLASS);
        $targetEntityType = $this->getEntityType(static::TARGET_ENTITY_CLASS);

        $response = $this->cget(['entity' => $entityType]);

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => $entityType,
                        'id'            => '<toString(@entity2_1->id)>',
                        'relationships' => [
                            'targetSideOfUnidirectionalManyToOne'  => [
                                'data' => [
                                    ['type' => $targetEntityType, 'id' => '<toString(@entity1_1->id)>'],
                                    ['type' => $targetEntityType, 'id' => '<toString(@entity1_2->id)>']
                                ]
                            ],
                            'targetSideOfUnidirectionalManyToMany' => [
                                'data' => [
                                    ['type' => $targetEntityType, 'id' => '<toString(@entity1_1->id)>'],
                                    ['type' => $targetEntityType, 'id' => '<toString(@entity1_2->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'          => $entityType,
                        'id'            => '<toString(@entity2_2->id)>',
                        'relationships' => [
                            'targetSideOfUnidirectionalManyToOne'  => [
                                'data' => [
                                    ['type' => $targetEntityType, 'id' => '<toString(@entity1_3->id)>']
                                ]
                            ],
                            'targetSideOfUnidirectionalManyToMany' => [
                                'data' => [
                                    ['type' => $targetEntityType, 'id' => '<toString(@entity1_1->id)>'],
                                    ['type' => $targetEntityType, 'id' => '<toString(@entity1_3->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'          => $entityType,
                        'id'            => '<toString(@entity2_3->id)>',
                        'relationships' => [
                            'targetSideOfUnidirectionalManyToOne'  => [
                                'data' => [
                                    ['type' => $targetEntityType, 'id' => '<toString(@entity1_4->id)>']
                                ]
                            ],
                            'targetSideOfUnidirectionalManyToMany' => [
                                'data' => [
                                    ['type' => $targetEntityType, 'id' => '<toString(@entity1_2->id)>'],
                                    ['type' => $targetEntityType, 'id' => '<toString(@entity1_3->id)>'],
                                    ['type' => $targetEntityType, 'id' => '<toString(@entity1_4->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'          => $entityType,
                        'id'            => '<toString(@entity2_4->id)>',
                        'relationships' => [
                            'targetSideOfUnidirectionalManyToOne'  => [
                                'data' => []
                            ],
                            'targetSideOfUnidirectionalManyToMany' => [
                                'data' => []
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetForEntityWithTargetSideOfUnidirectionalAssociationsAndWithIncludeFilter()
    {
        $entityType = $this->getEntityType(static::ENTITY_CLASS);
        $targetEntityType = $this->getEntityType(static::TARGET_ENTITY_CLASS);

        $response = $this->get(
            ['entity' => $entityType, 'id' => '<toString(@entity2_2->id)>'],
            ['include' => 'targetSideOfUnidirectionalManyToOne,targetSideOfUnidirectionalManyToMany']
        );

        $this->assertResponseContains(
            [
                'data'     => [
                    'type'          => $entityType,
                    'id'            => '<toString(@entity2_2->id)>',
                    'relationships' => [
                        'targetSideOfUnidirectionalManyToOne'  => [
                            'data' => [
                                ['type' => $targetEntityType, 'id' => '<toString(@entity1_3->id)>']
                            ]
                        ],
                        'targetSideOfUnidirectionalManyToMany' => [
                            'data' => [
                                ['type' => $targetEntityType, 'id' => '<toString(@entity1_1->id)>'],
                                ['type' => $targetEntityType, 'id' => '<toString(@entity1_3->id)>']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'          => $targetEntityType,
                        'id'            => '<toString(@entity1_3->id)>',
                        'attributes'    => [
                            'name' => 'Entity 1 (3)'
                        ],
                        'relationships' => [
                            'uniM2O' => [
                                'data' => ['type' => $entityType, 'id' => '<toString(@entity2_2->id)>']
                            ],
                            'uniM2M' => [
                                'data' => [
                                    ['type' => $entityType, 'id' => '<toString(@entity2_2->id)>'],
                                    ['type' => $entityType, 'id' => '<toString(@entity2_3->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'          => $targetEntityType,
                        'id'            => '<toString(@entity1_1->id)>',
                        'attributes'    => [
                            'name' => 'Entity 1 (1)'
                        ],
                        'relationships' => [
                            'uniM2O' => [
                                'data' => ['type' => $entityType, 'id' => '<toString(@entity2_1->id)>']
                            ],
                            'uniM2M' => [
                                'data' => [
                                    ['type' => $entityType, 'id' => '<toString(@entity2_1->id)>'],
                                    ['type' => $entityType, 'id' => '<toString(@entity2_2->id)>']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetForEntityWithOwningSideOfUnidirectionalAssociationsAndWithIncludeFilter()
    {
        $entityType = $this->getEntityType(static::ENTITY_CLASS);
        $targetEntityType = $this->getEntityType(static::TARGET_ENTITY_CLASS);

        $response = $this->get(
            ['entity' => $targetEntityType, 'id' => '<toString(@entity1_2->id)>'],
            ['include' => 'uniM2O,uniM2M']
        );

        $this->assertResponseContains(
            [
                'data'     => [
                    'type'          => $targetEntityType,
                    'id'            => '<toString(@entity1_2->id)>',
                    'relationships' => [
                        'uniM2O' => [
                            'data' => ['type' => $entityType, 'id' => '<toString(@entity2_1->id)>']
                        ],
                        'uniM2M' => [
                            'data' => [
                                ['type' => $entityType, 'id' => '<toString(@entity2_1->id)>'],
                                ['type' => $entityType, 'id' => '<toString(@entity2_3->id)>']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'          => $entityType,
                        'id'            => '<toString(@entity2_1->id)>',
                        'attributes'    => [
                            'name' => 'Entity 2 (1)'
                        ],
                        'relationships' => [
                            'targetSideOfUnidirectionalManyToOne'  => [
                                'data' => [
                                    ['type' => $targetEntityType, 'id' => '<toString(@entity1_1->id)>'],
                                    ['type' => $targetEntityType, 'id' => '<toString(@entity1_2->id)>']
                                ]
                            ],
                            'targetSideOfUnidirectionalManyToMany' => [
                                'data' => [
                                    ['type' => $targetEntityType, 'id' => '<toString(@entity1_1->id)>'],
                                    ['type' => $targetEntityType, 'id' => '<toString(@entity1_2->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'          => $entityType,
                        'id'            => '<toString(@entity2_3->id)>',
                        'attributes'    => [
                            'name' => 'Entity 2 (3)'
                        ],
                        'relationships' => [
                            'targetSideOfUnidirectionalManyToOne'  => [
                                'data' => [
                                    ['type' => $targetEntityType, 'id' => '<toString(@entity1_4->id)>']
                                ]
                            ],
                            'targetSideOfUnidirectionalManyToMany' => [
                                'data' => [
                                    ['type' => $targetEntityType, 'id' => '<toString(@entity1_2->id)>'],
                                    ['type' => $targetEntityType, 'id' => '<toString(@entity1_3->id)>'],
                                    ['type' => $targetEntityType, 'id' => '<toString(@entity1_4->id)>']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testCreateForEntityWithTargetSideOfUnidirectionalManyToOne()
    {
        $associationName = 'targetSideOfUnidirectionalManyToOne';
        $entityType = $this->getEntityType(static::ENTITY_CLASS);
        $targetEntityType = $this->getEntityType(static::TARGET_ENTITY_CLASS);

        $targetEntity12Id = $this->getReference('entity1_2')->getId();
        $targetEntity15Id = $this->getReference('entity1_5')->getId();

        $data = [
            'data' => [
                'type'          => $entityType,
                'relationships' => [
                    $associationName => [
                        'data' => [
                            ['type' => $targetEntityType, 'id' => (string)$targetEntity12Id],
                            ['type' => $targetEntityType, 'id' => (string)$targetEntity15Id]
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => $entityType],
            $data
        );
        $this->assertRelationshipEquals($data, $associationName, $response);

        // test that the target entities were updated
        $entityId = (int)$this->getResourceId($response);
        self::assertTrue(null !== $this->getEntity(static::ENTITY_CLASS, $entityId));
        self::assertSame(
            $entityId,
            $this->getEntity(static::TARGET_ENTITY_CLASS, $targetEntity12Id)->getUniM2O()->getId()
        );
        self::assertSame(
            $entityId,
            $this->getEntity(static::TARGET_ENTITY_CLASS, $targetEntity15Id)->getUniM2O()->getId()
        );

        // test that changed data are returned by "get" action
        $this->getEntityManager()->clear();
        $response = $this->get(
            ['entity' => $entityType, 'id' => (string)$entityId]
        );
        $this->assertRelationshipEquals($data, $associationName, $response);
    }

    public function testCreateForEntityWithTargetSideOfUnidirectionalManyToMany()
    {
        $associationName = 'targetSideOfUnidirectionalManyToMany';
        $entityType = $this->getEntityType(static::ENTITY_CLASS);
        $targetEntityType = $this->getEntityType(static::TARGET_ENTITY_CLASS);

        $entity21Id = $this->getReference('entity2_1')->getId();
        $entity23Id = $this->getReference('entity2_3')->getId();
        $targetEntity12Id = $this->getReference('entity1_2')->getId();
        $targetEntity15Id = $this->getReference('entity1_5')->getId();

        $data = [
            'data' => [
                'type'          => $entityType,
                'relationships' => [
                    $associationName => [
                        'data' => [
                            ['type' => $targetEntityType, 'id' => (string)$targetEntity12Id],
                            ['type' => $targetEntityType, 'id' => (string)$targetEntity15Id]
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => $entityType],
            $data
        );
        $this->assertRelationshipEquals($data, $associationName, $response);

        // test that the target entities were updated
        $entityId = (int)$this->getResourceId($response);
        self::assertTrue(null !== $this->getEntity(static::ENTITY_CLASS, $entityId));
        $this->assertToManyAssociation(
            [$entityId, $entity21Id, $entity23Id],
            $this->getEntity(static::TARGET_ENTITY_CLASS, $targetEntity12Id)->getUniM2M()
        );
        $this->assertToManyAssociation(
            [$entityId],
            $this->getEntity(static::TARGET_ENTITY_CLASS, $targetEntity15Id)->getUniM2M()
        );

        // test that changed data are returned by "get" action
        $this->getEntityManager()->clear();
        $response = $this->get(
            ['entity' => $entityType, 'id' => (string)$entityId]
        );
        $this->assertRelationshipEquals($data, $associationName, $response);
    }

    public function testUpdateForEntityWithTargetSideOfUnidirectionalManyToOne()
    {
        $associationName = 'targetSideOfUnidirectionalManyToOne';
        $entityType = $this->getEntityType(static::ENTITY_CLASS);
        $targetEntityType = $this->getEntityType(static::TARGET_ENTITY_CLASS);

        $entityId = $this->getReference('entity2_1')->getId();
        $targetEntity11Id = $this->getReference('entity1_1')->getId();
        $targetEntity12Id = $this->getReference('entity1_2')->getId();
        $targetEntity15Id = $this->getReference('entity1_5')->getId();

        $data = [
            'data' => [
                'type'          => $entityType,
                'id'            => (string)$entityId,
                'relationships' => [
                    $associationName => [
                        'data' => [
                            ['type' => $targetEntityType, 'id' => (string)$targetEntity12Id],
                            ['type' => $targetEntityType, 'id' => (string)$targetEntity15Id]
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            $data
        );
        $this->assertRelationshipEquals($data, $associationName, $response);

        // test that the target entities were updated
        self::assertTrue(
            null === $this->getEntity(static::TARGET_ENTITY_CLASS, $targetEntity11Id)->getUniM2O()
        );
        self::assertSame(
            $entityId,
            $this->getEntity(static::TARGET_ENTITY_CLASS, $targetEntity12Id)->getUniM2O()->getId()
        );
        self::assertSame(
            $entityId,
            $this->getEntity(static::TARGET_ENTITY_CLASS, $targetEntity15Id)->getUniM2O()->getId()
        );

        // test that changed data are returned by "get" action
        $this->getEntityManager()->clear();
        $response = $this->get(
            ['entity' => $entityType, 'id' => (string)$entityId]
        );
        $this->assertRelationshipEquals($data, $associationName, $response);
    }

    public function testUpdateForEntityWithTargetSideOfUnidirectionalManyToMany()
    {
        $associationName = 'targetSideOfUnidirectionalManyToMany';
        $entityType = $this->getEntityType(static::ENTITY_CLASS);
        $targetEntityType = $this->getEntityType(static::TARGET_ENTITY_CLASS);

        $entityId = $this->getReference('entity2_1')->getId();
        $entity22Id = $this->getReference('entity2_2')->getId();
        $entity23Id = $this->getReference('entity2_3')->getId();
        $targetEntity11Id = $this->getReference('entity1_1')->getId();
        $targetEntity12Id = $this->getReference('entity1_2')->getId();
        $targetEntity15Id = $this->getReference('entity1_5')->getId();

        $data = [
            'data' => [
                'type'          => $entityType,
                'id'            => (string)$entityId,
                'relationships' => [
                    $associationName => [
                        'data' => [
                            ['type' => $targetEntityType, 'id' => (string)$targetEntity12Id],
                            ['type' => $targetEntityType, 'id' => (string)$targetEntity15Id]
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            $data
        );
        $this->assertRelationshipEquals($data, $associationName, $response);

        // test that the target entities were updated
        $this->assertToManyAssociation(
            [$entity22Id],
            $this->getEntity(static::TARGET_ENTITY_CLASS, $targetEntity11Id)->getUniM2M()
        );
        $this->assertToManyAssociation(
            [$entityId, $entity23Id],
            $this->getEntity(static::TARGET_ENTITY_CLASS, $targetEntity12Id)->getUniM2M()
        );
        $this->assertToManyAssociation(
            [$entityId],
            $this->getEntity(static::TARGET_ENTITY_CLASS, $targetEntity15Id)->getUniM2M()
        );

        // test that changed data are returned by "get" action
        $this->getEntityManager()->clear();
        $response = $this->get(
            ['entity' => $entityType, 'id' => (string)$entityId]
        );
        $this->assertRelationshipEquals($data, $associationName, $response);
    }

    public function testGetSubresourceForUnidirectionalManyToOne()
    {
        $entityType = $this->getEntityType(static::ENTITY_CLASS);
        $targetEntityType = $this->getEntityType(static::TARGET_ENTITY_CLASS);

        $response = $this->getSubresource([
            'entity'      => $entityType,
            'id'          => '@entity2_1->id',
            'association' => 'targetSideOfUnidirectionalManyToOne'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => $targetEntityType,
                        'id'            => '<toString(@entity1_1->id)>',
                        'relationships' => [
                            'uniM2O' => [
                                'data' => ['type' => $entityType, 'id' => '<toString(@entity2_1->id)>']
                            ]
                        ]
                    ],
                    [
                        'type'          => $targetEntityType,
                        'id'            => '<toString(@entity1_2->id)>',
                        'relationships' => [
                            'uniM2O' => [
                                'data' => ['type' => $entityType, 'id' => '<toString(@entity2_1->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceForUnidirectionalManyToMany()
    {
        $entityType = $this->getEntityType(static::ENTITY_CLASS);
        $targetEntityType = $this->getEntityType(static::TARGET_ENTITY_CLASS);

        $response = $this->getSubresource([
            'entity'      => $entityType,
            'id'          => '@entity2_1->id',
            'association' => 'targetSideOfUnidirectionalManyToMany'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => $targetEntityType,
                        'id'            => '<toString(@entity1_1->id)>',
                        'relationships' => [
                            'uniM2M' => [
                                'data' => [
                                    ['type' => $entityType, 'id' => '<toString(@entity2_1->id)>'],
                                    ['type' => $entityType, 'id' => '<toString(@entity2_2->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'          => $targetEntityType,
                        'id'            => '<toString(@entity1_2->id)>',
                        'relationships' => [
                            'uniM2M' => [
                                'data' => [
                                    ['type' => $entityType, 'id' => '<toString(@entity2_1->id)>'],
                                    ['type' => $entityType, 'id' => '<toString(@entity2_3->id)>']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForUnidirectionalManyToOne()
    {
        $entityType = $this->getEntityType(static::ENTITY_CLASS);
        $targetEntityType = $this->getEntityType(static::TARGET_ENTITY_CLASS);

        $response = $this->getRelationship([
            'entity'      => $entityType,
            'id'          => '@entity2_1->id',
            'association' => 'targetSideOfUnidirectionalManyToOne'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => $targetEntityType, 'id' => '<toString(@entity1_1->id)>'],
                    ['type' => $targetEntityType, 'id' => '<toString(@entity1_2->id)>']
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForUnidirectionalManyToMany()
    {
        $entityType = $this->getEntityType(static::ENTITY_CLASS);
        $targetEntityType = $this->getEntityType(static::TARGET_ENTITY_CLASS);

        $response = $this->getRelationship([
            'entity'      => $entityType,
            'id'          => '@entity2_1->id',
            'association' => 'targetSideOfUnidirectionalManyToMany'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => $targetEntityType, 'id' => '<toString(@entity1_1->id)>'],
                    ['type' => $targetEntityType, 'id' => '<toString(@entity1_2->id)>']
                ]
            ],
            $response
        );
    }

    public function testUpdateRelationshipForUnidirectionalManyToOne()
    {
        $associationName = 'targetSideOfUnidirectionalManyToOne';
        $entityType = $this->getEntityType(static::ENTITY_CLASS);
        $targetEntityType = $this->getEntityType(static::TARGET_ENTITY_CLASS);

        $entityId = $this->getReference('entity2_1')->getId();
        $targetEntity11Id = $this->getReference('entity1_1')->getId();
        $targetEntity12Id = $this->getReference('entity1_2')->getId();
        $targetEntity15Id = $this->getReference('entity1_5')->getId();

        $data = [
            'data' => [
                ['type' => $targetEntityType, 'id' => (string)$targetEntity12Id],
                ['type' => $targetEntityType, 'id' => (string)$targetEntity15Id]
            ]
        ];
        $this->patchRelationship(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => $associationName],
            $data
        );

        // test that the target entities were updated
        self::assertTrue(
            null === $this->getEntity(static::TARGET_ENTITY_CLASS, $targetEntity11Id)->getUniM2O()
        );
        self::assertSame(
            $entityId,
            $this->getEntity(static::TARGET_ENTITY_CLASS, $targetEntity12Id)->getUniM2O()->getId()
        );
        self::assertSame(
            $entityId,
            $this->getEntity(static::TARGET_ENTITY_CLASS, $targetEntity15Id)->getUniM2O()->getId()
        );

        // test that changed data are returned by "get" action
        $this->getEntityManager()->clear();
        $response = $this->get(
            ['entity' => $entityType, 'id' => (string)$entityId]
        );
        $this->assertRelationshipEquals(
            ['data' => ['relationships' => [$associationName => $data]]],
            $associationName,
            $response
        );
    }

    public function testUpdateRelationshipForUnidirectionalManyToMany()
    {
        $associationName = 'targetSideOfUnidirectionalManyToMany';
        $entityType = $this->getEntityType(static::ENTITY_CLASS);
        $targetEntityType = $this->getEntityType(static::TARGET_ENTITY_CLASS);

        $entityId = $this->getReference('entity2_1')->getId();
        $entity22Id = $this->getReference('entity2_2')->getId();
        $entity23Id = $this->getReference('entity2_3')->getId();
        $targetEntity11Id = $this->getReference('entity1_1')->getId();
        $targetEntity12Id = $this->getReference('entity1_2')->getId();
        $targetEntity15Id = $this->getReference('entity1_5')->getId();

        $data = [
            'data' => [
                ['type' => $targetEntityType, 'id' => (string)$targetEntity12Id],
                ['type' => $targetEntityType, 'id' => (string)$targetEntity15Id]
            ]
        ];
        $this->patchRelationship(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => $associationName],
            $data
        );

        // test that the target entities were updated
        $this->assertToManyAssociation(
            [$entity22Id],
            $this->getEntity(static::TARGET_ENTITY_CLASS, $targetEntity11Id)->getUniM2M()
        );
        $this->assertToManyAssociation(
            [$entityId, $entity23Id],
            $this->getEntity(static::TARGET_ENTITY_CLASS, $targetEntity12Id)->getUniM2M()
        );
        $this->assertToManyAssociation(
            [$entityId],
            $this->getEntity(static::TARGET_ENTITY_CLASS, $targetEntity15Id)->getUniM2M()
        );

        // test that changed data are returned by "get" action
        $this->getEntityManager()->clear();
        $response = $this->get(
            ['entity' => $entityType, 'id' => (string)$entityId]
        );
        $this->assertRelationshipEquals(
            ['data' => ['relationships' => [$associationName => $data]]],
            $associationName,
            $response
        );
    }

    public function testAddRelationshipForUnidirectionalManyToOne()
    {
        $associationName = 'targetSideOfUnidirectionalManyToOne';
        $entityType = $this->getEntityType(static::ENTITY_CLASS);
        $targetEntityType = $this->getEntityType(static::TARGET_ENTITY_CLASS);

        $entityId = $this->getReference('entity2_1')->getId();
        $targetEntity11Id = $this->getReference('entity1_1')->getId();
        $targetEntity12Id = $this->getReference('entity1_2')->getId();
        $targetEntity15Id = $this->getReference('entity1_5')->getId();

        $data = [
            'data' => [
                ['type' => $targetEntityType, 'id' => (string)$targetEntity12Id],
                ['type' => $targetEntityType, 'id' => (string)$targetEntity15Id]
            ]
        ];
        $this->postRelationship(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => $associationName],
            $data
        );

        // test that the target entities were updated
        self::assertSame(
            $entityId,
            $this->getEntity(static::TARGET_ENTITY_CLASS, $targetEntity11Id)->getUniM2O()->getId()
        );
        self::assertSame(
            $entityId,
            $this->getEntity(static::TARGET_ENTITY_CLASS, $targetEntity12Id)->getUniM2O()->getId()
        );
        self::assertSame(
            $entityId,
            $this->getEntity(static::TARGET_ENTITY_CLASS, $targetEntity15Id)->getUniM2O()->getId()
        );

        // test that changed data are returned by "get" action
        $this->getEntityManager()->clear();
        $response = $this->get(
            ['entity' => $entityType, 'id' => (string)$entityId]
        );
        $this->assertRelationshipEquals(
            [
                'data' => [
                    'relationships' => [
                        $associationName => [
                            'data' => [
                                ['type' => $targetEntityType, 'id' => (string)$targetEntity11Id],
                                ['type' => $targetEntityType, 'id' => (string)$targetEntity12Id],
                                ['type' => $targetEntityType, 'id' => (string)$targetEntity15Id]
                            ]
                        ]
                    ]
                ]
            ],
            $associationName,
            $response
        );
    }

    public function testAddRelationshipForUnidirectionalManyToMany()
    {
        $associationName = 'targetSideOfUnidirectionalManyToMany';
        $entityType = $this->getEntityType(static::ENTITY_CLASS);
        $targetEntityType = $this->getEntityType(static::TARGET_ENTITY_CLASS);

        $entityId = $this->getReference('entity2_1')->getId();
        $entity22Id = $this->getReference('entity2_2')->getId();
        $entity23Id = $this->getReference('entity2_3')->getId();
        $targetEntity11Id = $this->getReference('entity1_1')->getId();
        $targetEntity12Id = $this->getReference('entity1_2')->getId();
        $targetEntity15Id = $this->getReference('entity1_5')->getId();

        $data = [
            'data' => [
                ['type' => $targetEntityType, 'id' => (string)$targetEntity12Id],
                ['type' => $targetEntityType, 'id' => (string)$targetEntity15Id]
            ]
        ];
        $this->postRelationship(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => $associationName],
            $data
        );

        // test that the target entities were updated
        $this->assertToManyAssociation(
            [$entityId, $entity22Id],
            $this->getEntity(static::TARGET_ENTITY_CLASS, $targetEntity11Id)->getUniM2M()
        );
        $this->assertToManyAssociation(
            [$entityId, $entity23Id],
            $this->getEntity(static::TARGET_ENTITY_CLASS, $targetEntity12Id)->getUniM2M()
        );
        $this->assertToManyAssociation(
            [$entityId],
            $this->getEntity(static::TARGET_ENTITY_CLASS, $targetEntity15Id)->getUniM2M()
        );

        // test that changed data are returned by "get" action
        $this->getEntityManager()->clear();
        $response = $this->get(
            ['entity' => $entityType, 'id' => (string)$entityId]
        );
        $this->assertRelationshipEquals(
            [
                'data' => [
                    'relationships' => [
                        $associationName => [
                            'data' => [
                                ['type' => $targetEntityType, 'id' => (string)$targetEntity11Id],
                                ['type' => $targetEntityType, 'id' => (string)$targetEntity12Id],
                                ['type' => $targetEntityType, 'id' => (string)$targetEntity15Id]
                            ]
                        ]
                    ]
                ]
            ],
            $associationName,
            $response
        );
    }

    public function testDeleteRelationshipForUnidirectionalManyToOne()
    {
        $associationName = 'targetSideOfUnidirectionalManyToOne';
        $entityType = $this->getEntityType(static::ENTITY_CLASS);
        $targetEntityType = $this->getEntityType(static::TARGET_ENTITY_CLASS);

        $entityId = $this->getReference('entity2_1')->getId();
        $targetEntity11Id = $this->getReference('entity1_1')->getId();
        $targetEntity12Id = $this->getReference('entity1_2')->getId();
        $targetEntity15Id = $this->getReference('entity1_5')->getId();

        $data = [
            'data' => [
                ['type' => $targetEntityType, 'id' => (string)$targetEntity12Id],
                ['type' => $targetEntityType, 'id' => (string)$targetEntity15Id]
            ]
        ];
        $this->deleteRelationship(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => $associationName],
            $data
        );

        // test that the target entities were updated
        self::assertSame(
            $entityId,
            $this->getEntity(static::TARGET_ENTITY_CLASS, $targetEntity11Id)->getUniM2O()->getId()
        );
        self::assertTrue(
            null === $this->getEntity(static::TARGET_ENTITY_CLASS, $targetEntity12Id)->getUniM2O()
        );
        self::assertTrue(
            null === $this->getEntity(static::TARGET_ENTITY_CLASS, $targetEntity15Id)->getUniM2O()
        );

        // test that changed data are returned by "get" action
        $this->getEntityManager()->clear();
        $response = $this->get(
            ['entity' => $entityType, 'id' => (string)$entityId]
        );
        $this->assertRelationshipEquals(
            [
                'data' => [
                    'relationships' => [
                        $associationName => [
                            'data' => [
                                ['type' => $targetEntityType, 'id' => (string)$targetEntity11Id]
                            ]
                        ]
                    ]
                ]
            ],
            $associationName,
            $response
        );
    }

    public function testDeleteRelationshipForUnidirectionalManyToMany()
    {
        $associationName = 'targetSideOfUnidirectionalManyToMany';
        $entityType = $this->getEntityType(static::ENTITY_CLASS);
        $targetEntityType = $this->getEntityType(static::TARGET_ENTITY_CLASS);

        $entityId = $this->getReference('entity2_1')->getId();
        $entity22Id = $this->getReference('entity2_2')->getId();
        $entity23Id = $this->getReference('entity2_3')->getId();
        $targetEntity11Id = $this->getReference('entity1_1')->getId();
        $targetEntity12Id = $this->getReference('entity1_2')->getId();
        $targetEntity15Id = $this->getReference('entity1_5')->getId();

        $data = [
            'data' => [
                ['type' => $targetEntityType, 'id' => (string)$targetEntity12Id],
                ['type' => $targetEntityType, 'id' => (string)$targetEntity15Id]
            ]
        ];
        $this->deleteRelationship(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => $associationName],
            $data
        );

        // test that the target entities were updated
        $this->assertToManyAssociation(
            [$entityId, $entity22Id],
            $this->getEntity(static::TARGET_ENTITY_CLASS, $targetEntity11Id)->getUniM2M()
        );
        $this->assertToManyAssociation(
            [$entity23Id],
            $this->getEntity(static::TARGET_ENTITY_CLASS, $targetEntity12Id)->getUniM2M()
        );
        $this->assertToManyAssociation(
            [],
            $this->getEntity(static::TARGET_ENTITY_CLASS, $targetEntity15Id)->getUniM2M()
        );

        // test that changed data are returned by "get" action
        $this->getEntityManager()->clear();
        $response = $this->get(
            ['entity' => $entityType, 'id' => (string)$entityId]
        );
        $this->assertRelationshipEquals(
            [
                'data' => [
                    'relationships' => [
                        $associationName => [
                            'data' => [
                                ['type' => $targetEntityType, 'id' => (string)$targetEntity11Id]
                            ]
                        ]
                    ]
                ]
            ],
            $associationName,
            $response
        );
    }
}
