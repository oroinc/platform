<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadEnumsData;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestOwner;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestTarget;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * Tests similar to EntityOverrideTest, but without "override_class" option.
 * These tests are needed to make sure that "override_class" option related changes does not affect regular entities.
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class EntityWithoutOverrideTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            LoadEnumsData::class,
            '@OroApiBundle/Tests/Functional/DataFixtures/entities_without_override_class.yml'
        ]);
    }

    /**
     * @param string[]   $expectedEntities The list of expected entity reference names
     * @param Collection $association      The association value
     */
    private function assertToManyAssociation(array $expectedEntities, Collection $association)
    {
        $associationIds = \array_map(
            function ($entity) {
                return $entity->id;
            },
            $association->toArray()
        );
        \sort($associationIds);
        $expectedIds = \array_map(
            function ($referenceName) {
                return $this->getReference($referenceName)->id;
            },
            $expectedEntities
        );
        \sort($expectedIds);
        self::assertEquals($expectedIds, $associationIds);
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'testapitargets', 'id' => '<toString(@target_1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapitargets',
                    'id'            => '<toString(@target_1->id)>',
                    'attributes'    => [
                        'name' => 'Target 1 (customized)'
                    ],
                    'relationships' => [
                        'owners' => [
                            'data' => [
                                ['type' => 'testapiowners', 'id' => '<toString(@owner_1->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetWithRelationships()
    {
        $response = $this->get(
            ['entity' => 'testapiowners', 'id' => '<toString(@owner_1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapiowners',
                    'id'            => '<toString(@owner_1->id)>',
                    'attributes'    => [
                        'name' => 'Owner 1'
                    ],
                    'relationships' => [
                        'target'  => [
                            'data' => ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>']
                        ],
                        'targets' => [
                            'data' => [
                                ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>'],
                                ['type' => 'testapitargets', 'id' => '<toString(@target_2->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetWhenRelationshipIsExpanded()
    {
        $response = $this->get(
            ['entity' => 'testapiowners', 'id' => '<toString(@owner_1->id)>'],
            [
                'fields'  => [
                    'testapiowners'  => 'name,target,targets',
                    'testapitargets' => 'name'
                ],
                'include' => 'target,targets'
            ]
        );

        $this->assertResponseContains(
            [
                'data'     => [
                    'type'          => 'testapiowners',
                    'id'            => '<toString(@owner_1->id)>',
                    'attributes'    => [
                        'name' => 'Owner 1'
                    ],
                    'relationships' => [
                        'target'  => [
                            'data' => ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>']
                        ],
                        'targets' => [
                            'data' => [
                                ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>'],
                                ['type' => 'testapitargets', 'id' => '<toString(@target_2->id)>']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'testapitargets',
                        'id'         => '<toString(@target_1->id)>',
                        'attributes' => [
                            'name' => 'Target 1 (customized)'
                        ]
                    ],
                    [
                        'type'       => 'testapitargets',
                        'id'         => '<toString(@target_2->id)>',
                        'attributes' => [
                            'name' => 'Target 2 (customized)'
                        ]
                    ]
                ]
            ],
            $response
        );
        $content = self::jsonToArray($response->getContent());
        $included = $content['included'];
        self::assertCount(2, $included);
        self::assertArrayNotHasKey('relationships', $included[0], 'target 1');
        self::assertArrayNotHasKey('relationships', $included[1], 'target 2');
    }

    public function testGetForEntityWithExtendedAssociation()
    {
        $response = $this->get(
            ['entity' => 'testapiactivities', 'id' => '<toString(@activity_1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapiactivities',
                    'id'            => '<toString(@activity_1->id)>',
                    'attributes'    => [
                        'name' => 'Activity 1'
                    ],
                    'relationships' => [
                        'activityTargets' => [
                            'data' => [
                                ['type' => 'testapiowners', 'id' => '<toString(@owner_1->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testFilterByExtendedAssociation()
    {
        $response = $this->cget(
            ['entity' => 'testapiactivities'],
            ['filter[activityTargets.testapiowners]' => '<toString(@owner_2->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => 'testapiactivities',
                        'id'            => '<toString(@activity_2->id)>',
                        'attributes'    => [
                            'name' => 'Activity 2'
                        ],
                        'relationships' => [
                            'activityTargets' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_2->id)>'],
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_2->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'          => 'testapiactivities',
                        'id'            => '<toString(@activity_4->id)>',
                        'attributes'    => [
                            'name' => 'Activity 4'
                        ],
                        'relationships' => [
                            'activityTargets' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_2->id)>']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testFilterByExtendedAssociationBySeveralTargetTypes()
    {
        $response = $this->cget(
            ['entity' => 'testapiactivities'],
            [
                'filter[activityTargets.testapiowners]'  => '<toString(@owner_2->id)>',
                'filter[activityTargets.testapitargets]' => '<toString(@target_2->id)>'
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => 'testapiactivities',
                        'id'            => '<toString(@activity_2->id)>',
                        'attributes'    => [
                            'name' => 'Activity 2'
                        ],
                        'relationships' => [
                            'activityTargets' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_2->id)>'],
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_2->id)>']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testSort()
    {
        $response = $this->cget(
            ['entity' => 'testapiowners'],
            ['sort' => '-id']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapiowners',
                        'id'         => '<toString(@owner_2->id)>',
                        'attributes' => [
                            'name' => 'Owner 2'
                        ]
                    ],
                    [
                        'type'       => 'testapiowners',
                        'id'         => '<toString(@owner_1->id)>',
                        'attributes' => [
                            'name' => 'Owner 1'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testSortUnsupportedSorter()
    {
        $response = $this->cget(
            ['entity' => 'testapiowners'],
            ['sort' => 'name'],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'sort constraint',
                'detail' => 'Sorting by "name" field is not supported.',
                'source' => ['parameter' => 'sort']
            ],
            $response
        );
    }

    public function testSortByIndexedField()
    {
        $response = $this->cget(
            ['entity' => 'testapitargets'],
            ['sort' => '-name']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapitargets',
                        'id'         => '<toString(@target_3->id)>',
                        'attributes' => [
                            'name' => 'Target 3 (customized)'
                        ]
                    ],
                    [
                        'type'       => 'testapitargets',
                        'id'         => '<toString(@target_2->id)>',
                        'attributes' => [
                            'name' => 'Target 2 (customized)'
                        ]
                    ],
                    [
                        'type'       => 'testapitargets',
                        'id'         => '<toString(@target_1->id)>',
                        'attributes' => [
                            'name' => 'Target 1 (customized)'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testSortIndexedFieldInAssociatedEntity()
    {
        $response = $this->cget(
            ['entity' => 'testapiowners'],
            ['sort' => '-target.name']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapiowners',
                        'id'         => '<toString(@owner_2->id)>',
                        'attributes' => [
                            'name' => 'Owner 2'
                        ]
                    ],
                    [
                        'type'       => 'testapiowners',
                        'id'         => '<toString(@owner_1->id)>',
                        'attributes' => [
                            'name' => 'Owner 1'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testSortByFirstLevelAssociationField()
    {
        $response = $this->cget(
            ['entity' => 'testapiowners'],
            ['sort' => '-target.id']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapiowners',
                        'id'         => '<toString(@owner_2->id)>',
                        'attributes' => [
                            'name' => 'Owner 2'
                        ]
                    ],
                    [
                        'type'       => 'testapiowners',
                        'id'         => '<toString(@owner_1->id)>',
                        'attributes' => [
                            'name' => 'Owner 1'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testSortBySecondLevelAssociationField()
    {
        $response = $this->cget(
            ['entity' => 'testapiowners'],
            ['sort' => '-target.owners.id']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapiowners',
                        'id'         => '<toString(@owner_2->id)>',
                        'attributes' => [
                            'name' => 'Owner 2'
                        ]
                    ],
                    [
                        'type'       => 'testapiowners',
                        'id'         => '<toString(@owner_1->id)>',
                        'attributes' => [
                            'name' => 'Owner 1'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testFilter()
    {
        $response = $this->cget(
            ['entity' => 'testapiowners'],
            ['filter[id]' => '<toString(@owner_2->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapiowners',
                        'id'         => '<toString(@owner_2->id)>',
                        'attributes' => [
                            'name' => 'Owner 2'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testFilterByUnsupportedFilter()
    {
        $response = $this->cget(
            ['entity' => 'testapiowners'],
            ['filter[name]' => 'test'],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The filter is not supported.',
                'source' => ['parameter' => 'filter[name]']
            ],
            $response
        );
    }

    public function testFilterByIndexedField()
    {
        $response = $this->cget(
            ['entity' => 'testapitargets'],
            ['filter[name]' => 'Target 2']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapitargets',
                        'id'         => '<toString(@target_2->id)>',
                        'attributes' => [
                            'name' => 'Target 2 (customized)'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testFilterByIndexedFieldInAssociatedEntity()
    {
        $response = $this->cget(
            ['entity' => 'testapiowners'],
            ['filter[target.name]' => 'Target 2']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapiowners',
                        'id'         => '<toString(@owner_2->id)>',
                        'attributes' => [
                            'name' => 'Owner 2'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testFilterByFirstLevelAssociationField()
    {
        $response = $this->cget(
            ['entity' => 'testapiowners'],
            ['filter[target.id]' => '<toString(@target_2->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapiowners',
                        'id'         => '<toString(@owner_2->id)>',
                        'attributes' => [
                            'name' => 'Owner 2'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testFilterBySecondLevelAssociationField()
    {
        $response = $this->cget(
            ['entity' => 'testapiowners'],
            ['filter[target.owners.id]' => '<toString(@owner_2->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapiowners',
                        'id'         => '<toString(@owner_2->id)>',
                        'attributes' => [
                            'name' => 'Owner 2'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetSubresource()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'testapiowners',
                'id'          => '<toString(@owner_1->id)>',
                'association' => 'targets'
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => 'testapitargets',
                        'id'            => '<toString(@target_1->id)>',
                        'attributes'    => [
                            'name' => 'Target 1 (customized)'
                        ],
                        'relationships' => [
                            'owners' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_1->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'          => 'testapitargets',
                        'id'            => '<toString(@target_2->id)>',
                        'attributes'    => [
                            'name' => 'Target 2 (customized)'
                        ],
                        'relationships' => [
                            'owners' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_1->id)>']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceWithSorting()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'testapiowners',
                'id'          => '<toString(@owner_1->id)>',
                'association' => 'targets'
            ],
            ['sort' => '-name']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapitargets',
                        'id'         => '<toString(@target_2->id)>',
                        'attributes' => [
                            'name' => 'Target 2 (customized)'
                        ]
                    ],
                    [
                        'type'       => 'testapitargets',
                        'id'         => '<toString(@target_1->id)>',
                        'attributes' => [
                            'name' => 'Target 1 (customized)'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceWithFiltering()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'testapiowners',
                'id'          => '<toString(@owner_1->id)>',
                'association' => 'targets'
            ],
            ['filter[name]' => 'Target 2']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapitargets',
                        'id'         => '<toString(@target_2->id)>',
                        'attributes' => [
                            'name' => 'Target 2 (customized)'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationship()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'testapiowners',
                'id'          => '<toString(@owner_1->id)>',
                'association' => 'targets'
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>'],
                    ['type' => 'testapitargets', 'id' => '<toString(@target_2->id)>']
                ]
            ],
            $response
        );
    }

    public function testCreateWithoutRelationships()
    {
        $response = $this->post(
            ['entity' => 'testapiowners'],
            [
                'data' => [
                    'type'       => 'testapiowners',
                    'attributes' => [
                        'name' => 'New Owner'
                    ]
                ]
            ]
        );
        $entityId = $this->getResourceId($response);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapiowners',
                    'id'         => $entityId,
                    'attributes' => [
                        'name' => 'New Owner'
                    ]
                ]
            ],
            $response
        );

        /** @var TestOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOwner::class,
            (int)$entityId
        );
        self::assertEquals('New Owner', $entity->name);
    }

    public function testCreateWithToOneRelationship()
    {
        $response = $this->post(
            ['entity' => 'testapiowners'],
            [
                'data' => [
                    'type'          => 'testapiowners',
                    'relationships' => [
                        'target' => [
                            'data' => ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>']
                        ]
                    ]
                ]
            ]
        );
        $entityId = $this->getResourceId($response);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapiowners',
                    'id'            => $entityId,
                    'relationships' => [
                        'target' => [
                            'data' => ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>']
                        ]
                    ]
                ]
            ],
            $response
        );

        /** @var TestOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOwner::class,
            (int)$entityId
        );
        self::assertEquals($this->getReference('target_1')->id, $entity->getTarget()->id);
    }

    public function testCreateWithToManyRelationship()
    {
        $response = $this->post(
            ['entity' => 'testapiowners'],
            [
                'data' => [
                    'type'          => 'testapiowners',
                    'relationships' => [
                        'targets' => [
                            'data' => [
                                ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>']
                            ]
                        ]
                    ]
                ]
            ]
        );
        $entityId = $this->getResourceId($response);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapiowners',
                    'id'            => $entityId,
                    'relationships' => [
                        'targets' => [
                            'data' => [
                                ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );

        /** @var TestOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOwner::class,
            (int)$entityId
        );
        $this->assertToManyAssociation(['target_1'], $entity->getTargets());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreateWithIncludedRelationships()
    {
        $response = $this->post(
            ['entity' => 'testapiowners'],
            [
                'data'     => [
                    'type'          => 'testapiowners',
                    'attributes'    => [
                        'name' => 'New Owner'
                    ],
                    'relationships' => [
                        'target'  => [
                            'data' => ['type' => 'testapitargets', 'id' => 'target1']
                        ],
                        'targets' => [
                            'data' => [
                                ['type' => 'testapitargets', 'id' => 'target2'],
                                ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>'],
                                ['type' => 'testapitargets', 'id' => '<toString(@target_2->id)>']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'testapitargets',
                        'id'         => 'target1',
                        'attributes' => [
                            'name' => 'New Target 1'
                        ]
                    ],
                    [
                        'type'       => 'testapitargets',
                        'id'         => 'target2',
                        'attributes' => [
                            'name' => 'New Target 2'
                        ]
                    ],
                    [
                        'type'       => 'testapitargets',
                        'id'         => '<toString(@target_1->id)>',
                        'meta'       => [
                            'update' => true
                        ],
                        'attributes' => [
                            'name' => 'Updated Target 1'
                        ]
                    ]
                ]
            ]
        );
        $entityId = $this->getResourceId($response);
        $targetRepo = $this->getEntityManager()->getRepository(TestTarget::class);
        $target1 = $targetRepo->findOneBy(['name' => 'New Target 1']);
        $target2 = $targetRepo->findOneBy(['name' => 'New Target 2']);
        $updatedTarget1 = $targetRepo->findOneBy(['name' => 'Updated Target 1']);
        $existingTarget2 = $targetRepo->findOneBy(['name' => 'Target 2']);
        self::assertNotNull($target1);
        self::assertNotNull($target2);
        self::assertNotNull($updatedTarget1);
        self::assertNotNull($existingTarget2);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapiowners',
                    'id'            => $entityId,
                    'attributes'    => [
                        'name' => 'New Owner'
                    ],
                    'relationships' => [
                        'target'  => [
                            'data' => ['type' => 'testapitargets', 'id' => (string)$target1->id]
                        ],
                        'targets' => [
                            'data' => [
                                ['type' => 'testapitargets', 'id' => (string)$target2->id],
                                ['type' => 'testapitargets', 'id' => (string)$updatedTarget1->id],
                                ['type' => 'testapitargets', 'id' => (string)$existingTarget2->id]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );

        /** @var TestOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOwner::class,
            (int)$entityId
        );
        self::assertEquals('New Owner', $entity->name);
        self::assertEquals($target1->id, $entity->getTarget()->id);
        self::assertCount(3, $entity->getTargets());
        $expectedIds = [$target2->id, $updatedTarget1->id, $existingTarget2->id];
        $actualIds = $entity->getTargets()->map(function (TestTarget $target) {
            return $target->id;
        })->toArray();
        sort($expectedIds);
        sort($actualIds);
        self::assertEquals($expectedIds, $actualIds);
    }

    public function testDelete()
    {
        $entityIdToDelete = $this->getReference('owner_1')->id;
        $this->delete(
            ['entity' => 'testapiowners', 'id' => (string)$entityIdToDelete]
        );

        self::assertNull($this->getEntityManager()->find(TestOwner::class, $entityIdToDelete));
    }

    public function testDeleteList()
    {
        $entityIdToDelete = $this->getReference('owner_1')->id;
        $this->cdelete(
            ['entity' => 'testapiowners'],
            ['filter' => ['id' => (string)$entityIdToDelete]]
        );

        self::assertNull($this->getEntityManager()->find(TestOwner::class, $entityIdToDelete));
    }

    public function testDeleteListWithTotalAndDeletedCounts()
    {
        $entityIdToDelete = $this->getReference('owner_1')->id;
        $response = $this->cdelete(
            ['entity' => 'testapiowners'],
            ['filter' => ['id' => (string)$entityIdToDelete]],
            ['HTTP_X-Include' => 'totalCount;deletedCount']
        );

        self::assertEquals(1, $response->headers->get('X-Include-Total-Count'), 'totalCount');
        self::assertEquals(1, $response->headers->get('X-Include-Deleted-Count'), 'deletedCount');

        self::assertNull($this->getEntityManager()->find(TestOwner::class, $entityIdToDelete));
    }

    public function testUpdateAttribute()
    {
        $response = $this->patch(
            ['entity' => 'testapiowners', 'id' => '<toString(@owner_1->id)>'],
            [
                'data' => [
                    'type'       => 'testapiowners',
                    'id'         => '<toString(@owner_1->id)>',
                    'attributes' => [
                        'name' => 'Owner 1 (updated)'
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapiowners',
                    'id'         => '<toString(@owner_1->id)>',
                    'attributes' => [
                        'name' => 'Owner 1 (updated)'
                    ]
                ]
            ],
            $response
        );

        /** @var TestOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOwner::class,
            $this->getReference('owner_1')->id
        );
        self::assertEquals('Owner 1 (updated)', $entity->name);
    }

    public function testUpdateToOneRelationship()
    {
        $response = $this->patch(
            ['entity' => 'testapiowners', 'id' => '<toString(@owner_1->id)>'],
            [
                'data' => [
                    'type'          => 'testapiowners',
                    'id'            => '<toString(@owner_1->id)>',
                    'relationships' => [
                        'target' => [
                            'data' => [
                                'type' => 'testapitargets',
                                'id'   => '<toString(@target_2->id)>'
                            ]
                        ]
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapiowners',
                    'id'            => '<toString(@owner_1->id)>',
                    'relationships' => [
                        'target' => [
                            'data' => [
                                'type' => 'testapitargets',
                                'id'   => '<toString(@target_2->id)>'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );

        /** @var TestOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOwner::class,
            $this->getReference('owner_1')->id
        );
        self::assertEquals($this->getReference('target_2')->id, $entity->getTarget()->id);
    }

    public function testUpdateToOneRelationshipSetNull()
    {
        $response = $this->patch(
            ['entity' => 'testapiowners', 'id' => '<toString(@owner_1->id)>'],
            [
                'data' => [
                    'type'          => 'testapiowners',
                    'id'            => '<toString(@owner_1->id)>',
                    'relationships' => [
                        'target' => [
                            'data' => null
                        ]
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapiowners',
                    'id'            => '<toString(@owner_1->id)>',
                    'relationships' => [
                        'target' => [
                            'data' => null
                        ]
                    ]
                ]
            ],
            $response
        );

        /** @var TestOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOwner::class,
            $this->getReference('owner_1')->id
        );
        self::assertNull($entity->getTarget());
    }

    public function testUpdateToManyRelationshipAddItems()
    {
        $response = $this->patch(
            ['entity' => 'testapiowners', 'id' => '<toString(@owner_1->id)>'],
            [
                'data' => [
                    'type'          => 'testapiowners',
                    'id'            => '<toString(@owner_1->id)>',
                    'relationships' => [
                        'targets' => [
                            'data' => [
                                ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>'],
                                ['type' => 'testapitargets', 'id' => '<toString(@target_2->id)>'],
                                ['type' => 'testapitargets', 'id' => '<toString(@target_3->id)>']
                            ]
                        ]
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapiowners',
                    'id'            => '<toString(@owner_1->id)>',
                    'relationships' => [
                        'targets' => [
                            'data' => [
                                ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>'],
                                ['type' => 'testapitargets', 'id' => '<toString(@target_2->id)>'],
                                ['type' => 'testapitargets', 'id' => '<toString(@target_3->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );

        /** @var TestOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOwner::class,
            $this->getReference('owner_1')->id
        );
        $this->assertToManyAssociation(['target_1', 'target_2', 'target_3'], $entity->getTargets());
    }

    public function testUpdateToManyRelationshipRemoveItems()
    {
        $response = $this->patch(
            ['entity' => 'testapiowners', 'id' => '<toString(@owner_1->id)>'],
            [
                'data' => [
                    'type'          => 'testapiowners',
                    'id'            => '<toString(@owner_1->id)>',
                    'relationships' => [
                        'targets' => [
                            'data' => [
                                ['type' => 'testapitargets', 'id' => '<toString(@target_2->id)>']
                            ]
                        ]
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapiowners',
                    'id'            => '<toString(@owner_1->id)>',
                    'relationships' => [
                        'targets' => [
                            'data' => [
                                ['type' => 'testapitargets', 'id' => '<toString(@target_2->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );

        /** @var TestOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOwner::class,
            $this->getReference('owner_1')->id
        );
        $this->assertToManyAssociation(['target_2'], $entity->getTargets());
    }

    public function testUpdateToManyRelationshipReplaceItems()
    {
        $response = $this->patch(
            ['entity' => 'testapiowners', 'id' => '<toString(@owner_1->id)>'],
            [
                'data' => [
                    'type'          => 'testapiowners',
                    'id'            => '<toString(@owner_1->id)>',
                    'relationships' => [
                        'targets' => [
                            'data' => [
                                ['type' => 'testapitargets', 'id' => '<toString(@target_3->id)>'],
                                ['type' => 'testapitargets', 'id' => '<toString(@target_2->id)>']
                            ]
                        ]
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapiowners',
                    'id'            => '<toString(@owner_1->id)>',
                    'relationships' => [
                        'targets' => [
                            'data' => [
                                ['type' => 'testapitargets', 'id' => '<toString(@target_3->id)>'],
                                ['type' => 'testapitargets', 'id' => '<toString(@target_2->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );

        /** @var TestOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOwner::class,
            $this->getReference('owner_1')->id
        );
        $this->assertToManyAssociation(['target_3', 'target_2'], $entity->getTargets());
    }

    public function testUpdateToManyRelationshipSetEmpty()
    {
        $response = $this->patch(
            ['entity' => 'testapiowners', 'id' => '<toString(@owner_1->id)>'],
            [
                'data' => [
                    'type'          => 'testapiowners',
                    'id'            => '<toString(@owner_1->id)>',
                    'relationships' => [
                        'targets' => [
                            'data' => []
                        ]
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapiowners',
                    'id'            => '<toString(@owner_1->id)>',
                    'relationships' => [
                        'targets' => [
                            'data' => []
                        ]
                    ]
                ]
            ],
            $response
        );

        /** @var TestOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOwner::class,
            $this->getReference('owner_1')->id
        );
        $this->assertToManyAssociation([], $entity->getTargets());
    }

    public function testUpdateRelationshipToOne()
    {
        $this->patchRelationship(
            [
                'entity'      => 'testapiowners',
                'id'          => '<toString(@owner_1->id)>',
                'association' => 'target'
            ],
            [
                'data' => [
                    'type' => 'testapitargets',
                    'id'   => '<toString(@target_2->id)>'
                ]
            ]
        );

        /** @var TestOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOwner::class,
            $this->getReference('owner_1')->id
        );
        self::assertEquals($this->getReference('target_2')->id, $entity->getTarget()->id);
    }

    public function testUpdateRelationshipToOneSetNull()
    {
        $this->patchRelationship(
            [
                'entity'      => 'testapiowners',
                'id'          => '<toString(@owner_1->id)>',
                'association' => 'target'
            ],
            [
                'data' => null
            ]
        );

        /** @var TestOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOwner::class,
            $this->getReference('owner_1')->id
        );
        self::assertNull($entity->getTarget());
    }

    public function testUpdateRelationshipToManyAddItems()
    {
        $this->patchRelationship(
            [
                'entity'      => 'testapiowners',
                'id'          => '<toString(@owner_1->id)>',
                'association' => 'targets'
            ],
            [
                'data' => [
                    ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>'],
                    ['type' => 'testapitargets', 'id' => '<toString(@target_2->id)>'],
                    ['type' => 'testapitargets', 'id' => '<toString(@target_3->id)>']
                ]
            ]
        );

        /** @var TestOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOwner::class,
            $this->getReference('owner_1')->id
        );
        $this->assertToManyAssociation(['target_1', 'target_2', 'target_3'], $entity->getTargets());
    }

    public function testUpdateRelationshipToManyRemoveItems()
    {
        $this->patchRelationship(
            [
                'entity'      => 'testapiowners',
                'id'          => '<toString(@owner_1->id)>',
                'association' => 'targets'
            ],
            [
                'data' => [
                    ['type' => 'testapitargets', 'id' => '<toString(@target_2->id)>']
                ]
            ]
        );

        /** @var TestOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOwner::class,
            $this->getReference('owner_1')->id
        );
        $this->assertToManyAssociation(['target_2'], $entity->getTargets());
    }

    public function testUpdateRelationshipToManyReplaceItems()
    {
        $this->patchRelationship(
            [
                'entity'      => 'testapiowners',
                'id'          => '<toString(@owner_1->id)>',
                'association' => 'targets'
            ],
            [
                'data' => [
                    ['type' => 'testapitargets', 'id' => '<toString(@target_3->id)>'],
                    ['type' => 'testapitargets', 'id' => '<toString(@target_2->id)>']
                ]
            ]
        );

        /** @var TestOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOwner::class,
            $this->getReference('owner_1')->id
        );
        $this->assertToManyAssociation(['target_3', 'target_2'], $entity->getTargets());
    }

    public function testUpdateRelationshipToManySetEmpty()
    {
        $this->patchRelationship(
            [
                'entity'      => 'testapiowners',
                'id'          => '<toString(@owner_1->id)>',
                'association' => 'targets'
            ],
            [
                'data' => []
            ]
        );

        /** @var TestOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOwner::class,
            $this->getReference('owner_1')->id
        );
        $this->assertToManyAssociation([], $entity->getTargets());
    }

    public function testAddRelationship()
    {
        $this->postRelationship(
            [
                'entity'      => 'testapiowners',
                'id'          => '<toString(@owner_2->id)>',
                'association' => 'targets'
            ],
            [
                'data' => [
                    ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>']
                ]
            ]
        );

        /** @var TestOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOwner::class,
            $this->getReference('owner_2')->id
        );
        $this->assertToManyAssociation(['target_1', 'target_2'], $entity->getTargets());
    }

    public function testRemoveRelationship()
    {
        $this->deleteRelationship(
            [
                'entity'      => 'testapiowners',
                'id'          => '<toString(@owner_1->id)>',
                'association' => 'targets'
            ],
            [
                'data' => [
                    ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>']
                ]
            ]
        );

        /** @var TestOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOwner::class,
            $this->getReference('owner_1')->id
        );
        $this->assertToManyAssociation(['target_2'], $entity->getTargets());
    }
}
