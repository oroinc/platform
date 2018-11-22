<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadEnumsData;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestOverrideClassOwner;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestOverrideClassTarget;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * Tests for API resource for a model inherited from ORM entity ("override_class" option).
 * Also see EntityWithoutOverrideTest that contains similar tests, but for regular entities.
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class EntityOverrideTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            LoadEnumsData::class,
            '@OroApiBundle/Tests/Functional/DataFixtures/entities_with_override_class.yml'
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

    public function testGetForOverriddenEntity()
    {
        $response = $this->get(
            ['entity' => 'testapioverrideclasstargets', 'id' => '<toString(@target_1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapioverrideclasstargets',
                    'id'            => '<toString(@target_1->id)>',
                    'attributes'    => [
                        'name' => 'Target 1 (customized by parent) (customized)'
                    ],
                    'relationships' => [
                        'owners' => [
                            'data' => [
                                ['type' => 'testapioverrideclassowners', 'id' => '<toString(@owner_1->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetWhenOverriddenEntityIsInRelationships()
    {
        $response = $this->get(
            ['entity' => 'testapioverrideclassowners', 'id' => '<toString(@owner_1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapioverrideclassowners',
                    'id'            => '<toString(@owner_1->id)>',
                    'attributes'    => [
                        'name' => 'Owner 1'
                    ],
                    'relationships' => [
                        'target'  => [
                            'data' => ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_1->id)>']
                        ],
                        'targets' => [
                            'data' => [
                                ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_1->id)>'],
                                ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_2->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetWhenRelationshipToOverriddenEntityIsExpanded()
    {
        $response = $this->get(
            ['entity' => 'testapioverrideclassowners', 'id' => '<toString(@owner_1->id)>'],
            [
                'fields'  => [
                    'testapioverrideclassowners'  => 'name,target,targets',
                    'testapioverrideclasstargets' => 'name'
                ],
                'include' => 'target,targets'
            ]
        );

        $this->assertResponseContains(
            [
                'data'     => [
                    'type'          => 'testapioverrideclassowners',
                    'id'            => '<toString(@owner_1->id)>',
                    'attributes'    => [
                        'name' => 'Owner 1'
                    ],
                    'relationships' => [
                        'target'  => [
                            'data' => ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_1->id)>']
                        ],
                        'targets' => [
                            'data' => [
                                ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_1->id)>'],
                                ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_2->id)>']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'testapioverrideclasstargets',
                        'id'         => '<toString(@target_1->id)>',
                        'attributes' => [
                            'name' => 'Target 1 (customized by parent) (customized)'
                        ]
                    ],
                    [
                        'type'       => 'testapioverrideclasstargets',
                        'id'         => '<toString(@target_2->id)>',
                        'attributes' => [
                            'name' => 'Target 2 (customized by parent) (customized)'
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

    public function testGetForEntityWithExtendedAssociationToOverriddenEntity()
    {
        $response = $this->get(
            ['entity' => 'testapioverrideclassactivities', 'id' => '<toString(@activity_1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapioverrideclassactivities',
                    'id'            => '<toString(@activity_1->id)>',
                    'attributes'    => [
                        'name' => 'Activity 1'
                    ],
                    'relationships' => [
                        'activityTargets' => [
                            'data' => [
                                ['type' => 'testapioverrideclassowners', 'id' => '<toString(@owner_1->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testFilterByExtendedAssociationToOverriddenEntity()
    {
        $response = $this->cget(
            ['entity' => 'testapioverrideclassactivities'],
            ['filter[activityTargets.testapioverrideclassowners]' => '<toString(@owner_2->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => 'testapioverrideclassactivities',
                        'id'            => '<toString(@activity_2->id)>',
                        'attributes'    => [
                            'name' => 'Activity 2'
                        ],
                        'relationships' => [
                            'activityTargets' => [
                                'data' => [
                                    ['type' => 'testapioverrideclassowners', 'id' => '<toString(@owner_2->id)>'],
                                    ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_2->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'          => 'testapioverrideclassactivities',
                        'id'            => '<toString(@activity_4->id)>',
                        'attributes'    => [
                            'name' => 'Activity 4'
                        ],
                        'relationships' => [
                            'activityTargets' => [
                                'data' => [
                                    ['type' => 'testapioverrideclassowners', 'id' => '<toString(@owner_2->id)>']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testFilterByExtendedAssociationToOverriddenEntityBySeveralTargetTypes()
    {
        $response = $this->cget(
            ['entity' => 'testapioverrideclassactivities'],
            [
                'filter[activityTargets.testapioverrideclassowners]'  => '<toString(@owner_2->id)>',
                'filter[activityTargets.testapioverrideclasstargets]' => '<toString(@target_2->id)>'
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => 'testapioverrideclassactivities',
                        'id'            => '<toString(@activity_2->id)>',
                        'attributes'    => [
                            'name' => 'Activity 2'
                        ],
                        'relationships' => [
                            'activityTargets' => [
                                'data' => [
                                    ['type' => 'testapioverrideclassowners', 'id' => '<toString(@owner_2->id)>'],
                                    ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_2->id)>']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testSortOverriddenEntity()
    {
        $response = $this->cget(
            ['entity' => 'testapioverrideclassowners'],
            ['sort' => '-id']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapioverrideclassowners',
                        'id'         => '<toString(@owner_2->id)>',
                        'attributes' => [
                            'name' => 'Owner 2'
                        ]
                    ],
                    [
                        'type'       => 'testapioverrideclassowners',
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

    public function testSortOverriddenEntityByUnsupportedSorter()
    {
        $response = $this->cget(
            ['entity' => 'testapioverrideclassowners'],
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

    public function testSortOverriddenEntityByIndexedField()
    {
        $response = $this->cget(
            ['entity' => 'testapioverrideclasstargets'],
            ['sort' => '-name']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapioverrideclasstargets',
                        'id'         => '<toString(@target_3->id)>',
                        'attributes' => [
                            'name' => 'Target 3 (customized by parent) (customized)'
                        ]
                    ],
                    [
                        'type'       => 'testapioverrideclasstargets',
                        'id'         => '<toString(@target_2->id)>',
                        'attributes' => [
                            'name' => 'Target 2 (customized by parent) (customized)'
                        ]
                    ],
                    [
                        'type'       => 'testapioverrideclasstargets',
                        'id'         => '<toString(@target_1->id)>',
                        'attributes' => [
                            'name' => 'Target 1 (customized by parent) (customized)'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testSortOverriddenEntityByIndexedFieldInAssociatedEntity()
    {
        $response = $this->cget(
            ['entity' => 'testapioverrideclassowners'],
            ['sort' => '-target.name']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapioverrideclassowners',
                        'id'         => '<toString(@owner_2->id)>',
                        'attributes' => [
                            'name' => 'Owner 2'
                        ]
                    ],
                    [
                        'type'       => 'testapioverrideclassowners',
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

    public function testSortOverriddenEntityByFirstLevelAssociationField()
    {
        $response = $this->cget(
            ['entity' => 'testapioverrideclassowners'],
            ['sort' => '-target.id']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapioverrideclassowners',
                        'id'         => '<toString(@owner_2->id)>',
                        'attributes' => [
                            'name' => 'Owner 2'
                        ]
                    ],
                    [
                        'type'       => 'testapioverrideclassowners',
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

    public function testSortOverriddenEntityBySecondLevelAssociationField()
    {
        $response = $this->cget(
            ['entity' => 'testapioverrideclassowners'],
            ['sort' => '-target.owners.id']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapioverrideclassowners',
                        'id'         => '<toString(@owner_2->id)>',
                        'attributes' => [
                            'name' => 'Owner 2'
                        ]
                    ],
                    [
                        'type'       => 'testapioverrideclassowners',
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

    public function testFilterOverriddenEntity()
    {
        $response = $this->cget(
            ['entity' => 'testapioverrideclassowners'],
            ['filter[id]' => '<toString(@owner_2->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapioverrideclassowners',
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

    public function testFilterOverriddenEntityByUnsupportedFilter()
    {
        $response = $this->cget(
            ['entity' => 'testapioverrideclassowners'],
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

    public function testFilterOverriddenEntityByIndexedField()
    {
        $response = $this->cget(
            ['entity' => 'testapioverrideclasstargets'],
            ['filter[name]' => 'Target 2']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapioverrideclasstargets',
                        'id'         => '<toString(@target_2->id)>',
                        'attributes' => [
                            'name' => 'Target 2 (customized by parent) (customized)'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testFilterOverriddenEntityByIndexedFieldInAssociatedEntity()
    {
        $response = $this->cget(
            ['entity' => 'testapioverrideclassowners'],
            ['filter[target.name]' => 'Target 2']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapioverrideclassowners',
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

    public function testFilterOverriddenEntityByFirstLevelAssociationField()
    {
        $response = $this->cget(
            ['entity' => 'testapioverrideclassowners'],
            ['filter[target.id]' => '<toString(@target_2->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapioverrideclassowners',
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

    public function testFilterOverriddenEntityBySecondLevelAssociationField()
    {
        $response = $this->cget(
            ['entity' => 'testapioverrideclassowners'],
            ['filter[target.owners.id]' => '<toString(@owner_2->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapioverrideclassowners',
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

    public function testGetSubresourceForOverriddenEntity()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'testapioverrideclassowners',
                'id'          => '<toString(@owner_1->id)>',
                'association' => 'targets'
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'          => 'testapioverrideclasstargets',
                        'id'            => '<toString(@target_1->id)>',
                        'attributes'    => [
                            'name' => 'Target 1 (customized by parent) (customized)'
                        ],
                        'relationships' => [
                            'owners' => [
                                'data' => [
                                    ['type' => 'testapioverrideclassowners', 'id' => '<toString(@owner_1->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'          => 'testapioverrideclasstargets',
                        'id'            => '<toString(@target_2->id)>',
                        'attributes'    => [
                            'name' => 'Target 2 (customized by parent) (customized)'
                        ],
                        'relationships' => [
                            'owners' => [
                                'data' => [
                                    ['type' => 'testapioverrideclassowners', 'id' => '<toString(@owner_1->id)>']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceForOverriddenEntityWithSorting()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'testapioverrideclassowners',
                'id'          => '<toString(@owner_1->id)>',
                'association' => 'targets'
            ],
            ['sort' => '-name']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapioverrideclasstargets',
                        'id'         => '<toString(@target_2->id)>',
                        'attributes' => [
                            'name' => 'Target 2 (customized by parent) (customized)'
                        ]
                    ],
                    [
                        'type'       => 'testapioverrideclasstargets',
                        'id'         => '<toString(@target_1->id)>',
                        'attributes' => [
                            'name' => 'Target 1 (customized by parent) (customized)'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceForOverriddenEntityWithFiltering()
    {
        $response = $this->getSubresource(
            [
                'entity'      => 'testapioverrideclassowners',
                'id'          => '<toString(@owner_1->id)>',
                'association' => 'targets'
            ],
            ['filter[name]' => 'Target 2']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapioverrideclasstargets',
                        'id'         => '<toString(@target_2->id)>',
                        'attributes' => [
                            'name' => 'Target 2 (customized by parent) (customized)'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForOverriddenEntity()
    {
        $response = $this->getRelationship(
            [
                'entity'      => 'testapioverrideclassowners',
                'id'          => '<toString(@owner_1->id)>',
                'association' => 'targets'
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_1->id)>'],
                    ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_2->id)>']
                ]
            ],
            $response
        );
    }

    public function testCreateOverriddenEntityWithoutRelationships()
    {
        $response = $this->post(
            ['entity' => 'testapioverrideclassowners'],
            [
                'data' => [
                    'type'       => 'testapioverrideclassowners',
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
                    'type'       => 'testapioverrideclassowners',
                    'id'         => $entityId,
                    'attributes' => [
                        'name' => 'New Owner'
                    ]
                ]
            ],
            $response
        );

        /** @var TestOverrideClassOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOverrideClassOwner::class,
            (int)$entityId
        );
        self::assertEquals('New Owner', $entity->name);
    }

    public function testCreateOverriddenEntityWithToOneRelationship()
    {
        $response = $this->post(
            ['entity' => 'testapioverrideclassowners'],
            [
                'data' => [
                    'type'          => 'testapioverrideclassowners',
                    'relationships' => [
                        'target' => [
                            'data' => ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_1->id)>']
                        ]
                    ]
                ]
            ]
        );
        $entityId = $this->getResourceId($response);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapioverrideclassowners',
                    'id'            => $entityId,
                    'relationships' => [
                        'target' => [
                            'data' => ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_1->id)>']
                        ]
                    ]
                ]
            ],
            $response
        );

        /** @var TestOverrideClassOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOverrideClassOwner::class,
            (int)$entityId
        );
        self::assertEquals($this->getReference('target_1')->id, $entity->getTarget()->id);
    }

    public function testCreateOverriddenEntityWithToManyRelationship()
    {
        $response = $this->post(
            ['entity' => 'testapioverrideclassowners'],
            [
                'data' => [
                    'type'          => 'testapioverrideclassowners',
                    'relationships' => [
                        'targets' => [
                            'data' => [
                                ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_1->id)>']
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
                    'type'          => 'testapioverrideclassowners',
                    'id'            => $entityId,
                    'relationships' => [
                        'targets' => [
                            'data' => [
                                ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_1->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );

        /** @var TestOverrideClassOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOverrideClassOwner::class,
            (int)$entityId
        );
        $this->assertToManyAssociation(['target_1'], $entity->getTargets());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreateOverriddenEntityWithIncludedRelationships()
    {
        $response = $this->post(
            ['entity' => 'testapioverrideclassowners'],
            [
                'data'     => [
                    'type'          => 'testapioverrideclassowners',
                    'attributes'    => [
                        'name' => 'New Owner'
                    ],
                    'relationships' => [
                        'target'  => [
                            'data' => ['type' => 'testapioverrideclasstargets', 'id' => 'target1']
                        ],
                        'targets' => [
                            'data' => [
                                ['type' => 'testapioverrideclasstargets', 'id' => 'target2'],
                                ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_1->id)>'],
                                ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_2->id)>']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'testapioverrideclasstargets',
                        'id'         => 'target1',
                        'attributes' => [
                            'name' => 'New Target 1'
                        ]
                    ],
                    [
                        'type'       => 'testapioverrideclasstargets',
                        'id'         => 'target2',
                        'attributes' => [
                            'name' => 'New Target 2'
                        ]
                    ],
                    [
                        'type'       => 'testapioverrideclasstargets',
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
        $targetRepo = $this->getEntityManager()->getRepository(TestOverrideClassTarget::class);
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
                    'type'          => 'testapioverrideclassowners',
                    'id'            => $entityId,
                    'attributes'    => [
                        'name' => 'New Owner'
                    ],
                    'relationships' => [
                        'target'  => [
                            'data' => ['type' => 'testapioverrideclasstargets', 'id' => (string)$target1->id]
                        ],
                        'targets' => [
                            'data' => [
                                ['type' => 'testapioverrideclasstargets', 'id' => (string)$target2->id],
                                ['type' => 'testapioverrideclasstargets', 'id' => (string)$updatedTarget1->id],
                                ['type' => 'testapioverrideclasstargets', 'id' => (string)$existingTarget2->id]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );

        /** @var TestOverrideClassOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOverrideClassOwner::class,
            (int)$entityId
        );
        self::assertEquals('New Owner', $entity->name);
        self::assertEquals($target1->id, $entity->getTarget()->id);
        self::assertCount(3, $entity->getTargets());
        $expectedIds = [$target2->id, $updatedTarget1->id, $existingTarget2->id];
        $actualIds = $entity->getTargets()->map(function (TestOverrideClassTarget $target) {
            return $target->id;
        })->toArray();
        sort($expectedIds);
        sort($actualIds);
        self::assertEquals($expectedIds, $actualIds);
    }

    public function testDeleteOverriddenEntity()
    {
        $entityIdToDelete = $this->getReference('owner_1')->id;
        $this->delete(
            ['entity' => 'testapioverrideclassowners', 'id' => (string)$entityIdToDelete]
        );

        self::assertNull($this->getEntityManager()->find(TestOverrideClassOwner::class, $entityIdToDelete));
    }

    public function testDeleteListOfOverriddenEntities()
    {
        $entityIdToDelete = $this->getReference('owner_1')->id;
        $this->cdelete(
            ['entity' => 'testapioverrideclassowners'],
            ['filter' => ['id' => (string)$entityIdToDelete]]
        );

        self::assertNull($this->getEntityManager()->find(TestOverrideClassOwner::class, $entityIdToDelete));
    }

    public function testDeleteListWithTotalAndDeletedCountsOfOverriddenEntities()
    {
        $entityIdToDelete = $this->getReference('owner_1')->id;
        $response = $this->cdelete(
            ['entity' => 'testapioverrideclassowners'],
            ['filter' => ['id' => (string)$entityIdToDelete]],
            ['HTTP_X-Include' => 'totalCount;deletedCount']
        );

        self::assertEquals(1, $response->headers->get('X-Include-Total-Count'), 'totalCount');
        self::assertEquals(1, $response->headers->get('X-Include-Deleted-Count'), 'deletedCount');

        self::assertNull($this->getEntityManager()->find(TestOverrideClassOwner::class, $entityIdToDelete));
    }

    public function testUpdateOverriddenEntityAttribute()
    {
        $response = $this->patch(
            ['entity' => 'testapioverrideclassowners', 'id' => '<toString(@owner_1->id)>'],
            [
                'data' => [
                    'type'       => 'testapioverrideclassowners',
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
                    'type'       => 'testapioverrideclassowners',
                    'id'         => '<toString(@owner_1->id)>',
                    'attributes' => [
                        'name' => 'Owner 1 (updated)'
                    ]
                ]
            ],
            $response
        );

        /** @var TestOverrideClassOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOverrideClassOwner::class,
            $this->getReference('owner_1')->id
        );
        self::assertEquals('Owner 1 (updated)', $entity->name);
    }

    public function testUpdateOverriddenEntityToOneRelationship()
    {
        $response = $this->patch(
            ['entity' => 'testapioverrideclassowners', 'id' => '<toString(@owner_1->id)>'],
            [
                'data' => [
                    'type'          => 'testapioverrideclassowners',
                    'id'            => '<toString(@owner_1->id)>',
                    'relationships' => [
                        'target' => [
                            'data' => [
                                'type' => 'testapioverrideclasstargets',
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
                    'type'          => 'testapioverrideclassowners',
                    'id'            => '<toString(@owner_1->id)>',
                    'relationships' => [
                        'target' => [
                            'data' => [
                                'type' => 'testapioverrideclasstargets',
                                'id'   => '<toString(@target_2->id)>'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );

        /** @var TestOverrideClassOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOverrideClassOwner::class,
            $this->getReference('owner_1')->id
        );
        self::assertEquals($this->getReference('target_2')->id, $entity->getTarget()->id);
    }

    public function testUpdateOverriddenEntityToOneRelationshipSetNull()
    {
        $response = $this->patch(
            ['entity' => 'testapioverrideclassowners', 'id' => '<toString(@owner_1->id)>'],
            [
                'data' => [
                    'type'          => 'testapioverrideclassowners',
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
                    'type'          => 'testapioverrideclassowners',
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

        /** @var TestOverrideClassOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOverrideClassOwner::class,
            $this->getReference('owner_1')->id
        );
        self::assertNull($entity->getTarget());
    }

    public function testUpdateOverriddenEntityToManyRelationshipAddItems()
    {
        $response = $this->patch(
            ['entity' => 'testapioverrideclassowners', 'id' => '<toString(@owner_1->id)>'],
            [
                'data' => [
                    'type'          => 'testapioverrideclassowners',
                    'id'            => '<toString(@owner_1->id)>',
                    'relationships' => [
                        'targets' => [
                            'data' => [
                                ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_1->id)>'],
                                ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_2->id)>'],
                                ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_3->id)>']
                            ]
                        ]
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapioverrideclassowners',
                    'id'            => '<toString(@owner_1->id)>',
                    'relationships' => [
                        'targets' => [
                            'data' => [
                                ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_1->id)>'],
                                ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_2->id)>'],
                                ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_3->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );

        /** @var TestOverrideClassOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOverrideClassOwner::class,
            $this->getReference('owner_1')->id
        );
        $this->assertToManyAssociation(['target_1', 'target_2', 'target_3'], $entity->getTargets());
    }

    public function testUpdateOverriddenEntityToManyRelationshipRemoveItems()
    {
        $response = $this->patch(
            ['entity' => 'testapioverrideclassowners', 'id' => '<toString(@owner_1->id)>'],
            [
                'data' => [
                    'type'          => 'testapioverrideclassowners',
                    'id'            => '<toString(@owner_1->id)>',
                    'relationships' => [
                        'targets' => [
                            'data' => [
                                ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_2->id)>']
                            ]
                        ]
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapioverrideclassowners',
                    'id'            => '<toString(@owner_1->id)>',
                    'relationships' => [
                        'targets' => [
                            'data' => [
                                ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_2->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );

        /** @var TestOverrideClassOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOverrideClassOwner::class,
            $this->getReference('owner_1')->id
        );
        $this->assertToManyAssociation(['target_2'], $entity->getTargets());
    }

    public function testUpdateOverriddenEntityToManyRelationshipReplaceItems()
    {
        $response = $this->patch(
            ['entity' => 'testapioverrideclassowners', 'id' => '<toString(@owner_1->id)>'],
            [
                'data' => [
                    'type'          => 'testapioverrideclassowners',
                    'id'            => '<toString(@owner_1->id)>',
                    'relationships' => [
                        'targets' => [
                            'data' => [
                                ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_3->id)>'],
                                ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_2->id)>']
                            ]
                        ]
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapioverrideclassowners',
                    'id'            => '<toString(@owner_1->id)>',
                    'relationships' => [
                        'targets' => [
                            'data' => [
                                ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_3->id)>'],
                                ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_2->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );

        /** @var TestOverrideClassOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOverrideClassOwner::class,
            $this->getReference('owner_1')->id
        );
        $this->assertToManyAssociation(['target_3', 'target_2'], $entity->getTargets());
    }

    public function testUpdateOverriddenEntityToManyRelationshipSetEmpty()
    {
        $response = $this->patch(
            ['entity' => 'testapioverrideclassowners', 'id' => '<toString(@owner_1->id)>'],
            [
                'data' => [
                    'type'          => 'testapioverrideclassowners',
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
                    'type'          => 'testapioverrideclassowners',
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

        /** @var TestOverrideClassOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOverrideClassOwner::class,
            $this->getReference('owner_1')->id
        );
        $this->assertToManyAssociation([], $entity->getTargets());
    }

    public function testUpdateRelationshipForOverriddenEntityToOne()
    {
        $this->patchRelationship(
            [
                'entity'      => 'testapioverrideclassowners',
                'id'          => '<toString(@owner_1->id)>',
                'association' => 'target'
            ],
            [
                'data' => [
                    'type' => 'testapioverrideclasstargets',
                    'id'   => '<toString(@target_2->id)>'
                ]
            ]
        );

        /** @var TestOverrideClassOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOverrideClassOwner::class,
            $this->getReference('owner_1')->id
        );
        self::assertEquals($this->getReference('target_2')->id, $entity->getTarget()->id);
    }

    public function testUpdateRelationshipForOverriddenEntityToOneSetNull()
    {
        $this->patchRelationship(
            [
                'entity'      => 'testapioverrideclassowners',
                'id'          => '<toString(@owner_1->id)>',
                'association' => 'target'
            ],
            [
                'data' => null
            ]
        );

        /** @var TestOverrideClassOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOverrideClassOwner::class,
            $this->getReference('owner_1')->id
        );
        self::assertNull($entity->getTarget());
    }

    public function testUpdateRelationshipForOverriddenEntityToManyAddItems()
    {
        $this->patchRelationship(
            [
                'entity'      => 'testapioverrideclassowners',
                'id'          => '<toString(@owner_1->id)>',
                'association' => 'targets'
            ],
            [
                'data' => [
                    ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_1->id)>'],
                    ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_2->id)>'],
                    ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_3->id)>']
                ]
            ]
        );

        /** @var TestOverrideClassOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOverrideClassOwner::class,
            $this->getReference('owner_1')->id
        );
        $this->assertToManyAssociation(['target_1', 'target_2', 'target_3'], $entity->getTargets());
    }

    public function testUpdateRelationshipForOverriddenEntityToManyRemoveItems()
    {
        $this->patchRelationship(
            [
                'entity'      => 'testapioverrideclassowners',
                'id'          => '<toString(@owner_1->id)>',
                'association' => 'targets'
            ],
            [
                'data' => [
                    ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_2->id)>']
                ]
            ]
        );

        /** @var TestOverrideClassOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOverrideClassOwner::class,
            $this->getReference('owner_1')->id
        );
        $this->assertToManyAssociation(['target_2'], $entity->getTargets());
    }

    public function testUpdateRelationshipForOverriddenEntityToManyReplaceItems()
    {
        $this->patchRelationship(
            [
                'entity'      => 'testapioverrideclassowners',
                'id'          => '<toString(@owner_1->id)>',
                'association' => 'targets'
            ],
            [
                'data' => [
                    ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_3->id)>'],
                    ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_2->id)>']
                ]
            ]
        );

        /** @var TestOverrideClassOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOverrideClassOwner::class,
            $this->getReference('owner_1')->id
        );
        $this->assertToManyAssociation(['target_3', 'target_2'], $entity->getTargets());
    }

    public function testUpdateRelationshipForOverriddenEntityToManySetEmpty()
    {
        $this->patchRelationship(
            [
                'entity'      => 'testapioverrideclassowners',
                'id'          => '<toString(@owner_1->id)>',
                'association' => 'targets'
            ],
            [
                'data' => []
            ]
        );

        /** @var TestOverrideClassOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOverrideClassOwner::class,
            $this->getReference('owner_1')->id
        );
        $this->assertToManyAssociation([], $entity->getTargets());
    }

    public function testAddRelationshipForOverriddenEntity()
    {
        $this->postRelationship(
            [
                'entity'      => 'testapioverrideclassowners',
                'id'          => '<toString(@owner_2->id)>',
                'association' => 'targets'
            ],
            [
                'data' => [
                    ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_1->id)>']
                ]
            ]
        );

        /** @var TestOverrideClassOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOverrideClassOwner::class,
            $this->getReference('owner_2')->id
        );
        $this->assertToManyAssociation(['target_1', 'target_2'], $entity->getTargets());
    }

    public function testRemoveRelationshipForOverriddenEntity()
    {
        $this->deleteRelationship(
            [
                'entity'      => 'testapioverrideclassowners',
                'id'          => '<toString(@owner_1->id)>',
                'association' => 'targets'
            ],
            [
                'data' => [
                    ['type' => 'testapioverrideclasstargets', 'id' => '<toString(@target_1->id)>']
                ]
            ]
        );

        /** @var TestOverrideClassOwner $entity */
        $entity = $this->getEntityManager()->find(
            TestOverrideClassOwner::class,
            $this->getReference('owner_1')->id
        );
        $this->assertToManyAssociation(['target_2'], $entity->getTargets());
    }
}
