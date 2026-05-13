<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\ExtIdRestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestActivity;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestOwner;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestTarget;
use Oro\Bundle\ApiBundle\Tests\Functional\ExtIdRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class ExtIdApiWithCustomExternalIdTest extends ExtIdRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/ext_id_entities.yml'
        ]);
    }

    private function getOwner(string $externalId): TestOwner
    {
        return $this->getEntityManager()
            ->getRepository(TestOwner::class)
            ->findOneBy(['external_id' => $externalId]);
    }

    private function getTarget(string $externalId): TestTarget
    {
        return $this->getEntityManager()
            ->getRepository(TestTarget::class)
            ->findOneBy(['externalId' => $externalId]);
    }

    private function getActivity(string $externalId): TestActivity
    {
        return $this->getEntityManager()
            ->getRepository(TestActivity::class)
            ->findOneBy(['external_id' => $externalId]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'testapiowners']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'testapiowners',
                        'id' => 'ext_owner_1',
                        'attributes' => [
                            'name' => 'Owner 1',
                            'dbId' => '@owner_1->id'
                        ],
                        'relationships' => [
                            'target' => [
                                'data' => ['type' => 'testapitargets', 'id' => 'ext_target_1']
                            ],
                            'targets' => [
                                'data' => [
                                    ['type' => 'testapitargets', 'id' => 'ext_target_1'],
                                    ['type' => 'testapitargets', 'id' => 'ext_target_2']
                                ]
                            ],
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => 'ext_activity_1'],
                                    ['type' => 'testapiactivities', 'id' => 'ext_activity_2']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiowners',
                        'id' => 'ext_owner_2',
                        'attributes' => [
                            'name' => 'Owner 2',
                            'dbId' => '@owner_2->id'
                        ],
                        'relationships' => [
                            'target' => [
                                'data' => null
                            ],
                            'targets' => [
                                'data' => [
                                    ['type' => 'testapitargets', 'id' => 'ext_target_1']
                                ]
                            ],
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => 'ext_activity_2']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListWithIncludedEntities(): void
    {
        $response = $this->cget(['entity' => 'testapiowners'], ['include' => 'target,targets,activityTestActivities']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'testapiowners',
                        'id' => 'ext_owner_1',
                        'attributes' => [
                            'name' => 'Owner 1',
                            'dbId' => '@owner_1->id'
                        ],
                        'relationships' => [
                            'target' => [
                                'data' => ['type' => 'testapitargets', 'id' => 'ext_target_1']
                            ],
                            'targets' => [
                                'data' => [
                                    ['type' => 'testapitargets', 'id' => 'ext_target_1'],
                                    ['type' => 'testapitargets', 'id' => 'ext_target_2']
                                ]
                            ],
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => 'ext_activity_1'],
                                    ['type' => 'testapiactivities', 'id' => 'ext_activity_2']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiowners',
                        'id' => 'ext_owner_2',
                        'attributes' => [
                            'name' => 'Owner 2',
                            'dbId' => '@owner_2->id'
                        ],
                        'relationships' => [
                            'target' => [
                                'data' => null
                            ],
                            'targets' => [
                                'data' => [
                                    ['type' => 'testapitargets', 'id' => 'ext_target_1']
                                ]
                            ],
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => 'ext_activity_2']
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'testapitargets',
                        'id' => 'ext_target_1',
                        'attributes' => [
                            'name' => 'Target 1 (customized)',
                            'dbId' => '@target_1->id'
                        ],
                        'relationships' => [
                            'owners' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => 'ext_owner_1'],
                                    ['type' => 'testapiowners', 'id' => 'ext_owner_2']
                                ]
                            ],
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => 'ext_activity_1'],
                                    ['type' => 'testapiactivities', 'id' => 'ext_activity_2']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapitargets',
                        'id' => 'ext_target_2',
                        'attributes' => [
                            'name' => 'Target 2 (customized)',
                            'dbId' => '@target_2->id'
                        ],
                        'relationships' => [
                            'owners' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => 'ext_owner_1']
                                ]
                            ],
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => 'ext_activity_2']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiactivities',
                        'id' => 'ext_activity_1',
                        'attributes' => [
                            'name' => 'Activity 1',
                            'dbId' => '@activity_1->id'
                        ],
                        'relationships' => [
                            'activityTargets' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => 'ext_owner_1'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_1->id)>'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_3->id)>'],
                                    ['type' => 'testapitargets', 'id' => 'ext_target_1']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiactivities',
                        'id' => 'ext_activity_2',
                        'attributes' => [
                            'name' => 'Activity 2',
                            'dbId' => '@activity_2->id'
                        ],
                        'relationships' => [
                            'activityTargets' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => 'ext_owner_1'],
                                    ['type' => 'testapiowners', 'id' => 'ext_owner_2'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_1->id)>'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_2->id)>'],
                                    ['type' => 'testapitargets', 'id' => 'ext_target_1'],
                                    ['type' => 'testapitargets', 'id' => 'ext_target_2']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListForAssociatedEntity(): void
    {
        $response = $this->cget(['entity' => 'testapiactivities']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'testapiactivities',
                        'id' => 'ext_activity_1',
                        'attributes' => [
                            'name' => 'Activity 1',
                            'dbId' => '@activity_1->id'
                        ],
                        'relationships' => [
                            'activityTargets' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => 'ext_owner_1'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_1->id)>'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_3->id)>'],
                                    ['type' => 'testapitargets', 'id' => 'ext_target_1']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiactivities',
                        'id' => 'ext_activity_2',
                        'attributes' => [
                            'name' => 'Activity 2',
                            'dbId' => '@activity_2->id'
                        ],
                        'relationships' => [
                            'activityTargets' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => 'ext_owner_1'],
                                    ['type' => 'testapiowners', 'id' => 'ext_owner_2'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_1->id)>'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_2->id)>'],
                                    ['type' => 'testapitargets', 'id' => 'ext_target_1'],
                                    ['type' => 'testapitargets', 'id' => 'ext_target_2']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListForAssociatedEntityWithIncludedEntities(): void
    {
        $response = $this->cget(['entity' => 'testapiactivities'], ['include' => 'activityTargets']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'testapiactivities',
                        'id' => 'ext_activity_1',
                        'attributes' => [
                            'name' => 'Activity 1',
                            'dbId' => '@activity_1->id'
                        ],
                        'relationships' => [
                            'activityTargets' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => 'ext_owner_1'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_1->id)>'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_3->id)>'],
                                    ['type' => 'testapitargets', 'id' => 'ext_target_1']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiactivities',
                        'id' => 'ext_activity_2',
                        'attributes' => [
                            'name' => 'Activity 2',
                            'dbId' => '@activity_2->id'
                        ],
                        'relationships' => [
                            'activityTargets' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => 'ext_owner_1'],
                                    ['type' => 'testapiowners', 'id' => 'ext_owner_2'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_1->id)>'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_2->id)>'],
                                    ['type' => 'testapitargets', 'id' => 'ext_target_1'],
                                    ['type' => 'testapitargets', 'id' => 'ext_target_2']
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'testapiowners',
                        'id' => 'ext_owner_1',
                        'attributes' => [
                            'name' => 'Owner 1',
                            'dbId' => '@owner_1->id'
                        ],
                        'relationships' => [
                            'target' => [
                                'data' => ['type' => 'testapitargets', 'id' => 'ext_target_1']
                            ],
                            'targets' => [
                                'data' => [
                                    ['type' => 'testapitargets', 'id' => 'ext_target_1'],
                                    ['type' => 'testapitargets', 'id' => 'ext_target_2']
                                ]
                            ],
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => 'ext_activity_1'],
                                    ['type' => 'testapiactivities', 'id' => 'ext_activity_2']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiactivitytargets',
                        'id' => '<toString(@activity_target_1->id)>',
                        'attributes' => [
                            'name' => 'Activity Target 1'
                        ],
                        'relationships' => [
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => 'ext_activity_1'],
                                    ['type' => 'testapiactivities', 'id' => 'ext_activity_2']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiactivitytargets',
                        'id' => '<toString(@activity_target_3->id)>',
                        'attributes' => [
                            'name' => 'Activity Target 3'
                        ],
                        'relationships' => [
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => 'ext_activity_1']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapitargets',
                        'id' => 'ext_target_1',
                        'attributes' => [
                            'name' => 'Target 1 (customized)',
                            'dbId' => '@target_1->id'
                        ],
                        'relationships' => [
                            'owners' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => 'ext_owner_1'],
                                    ['type' => 'testapiowners', 'id' => 'ext_owner_2']
                                ]
                            ],
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => 'ext_activity_1'],
                                    ['type' => 'testapiactivities', 'id' => 'ext_activity_2']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiowners',
                        'id' => 'ext_owner_2',
                        'attributes' => [
                            'name' => 'Owner 2',
                            'dbId' => '@owner_2->id'
                        ],
                        'relationships' => [
                            'target' => [
                                'data' => null
                            ],
                            'targets' => [
                                'data' => [
                                    ['type' => 'testapitargets', 'id' => 'ext_target_1']
                                ]
                            ],
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => 'ext_activity_2']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiactivitytargets',
                        'id' => '<toString(@activity_target_2->id)>',
                        'attributes' => [
                            'name' => 'Activity Target 2'
                        ],
                        'relationships' => [
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => 'ext_activity_2']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapitargets',
                        'id' => 'ext_target_2',
                        'attributes' => [
                            'name' => 'Target 2 (customized)',
                            'dbId' => '@target_2->id'
                        ],
                        'relationships' => [
                            'owners' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => 'ext_owner_1']
                                ]
                            ],
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => 'ext_activity_2']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWhenDbIdIsUsedInRelationshipsInsteadOfExternalId(): void
    {
        $data = [
            'data' => [
                'type' => 'testapiowners',
                'id' => 'new_owner_1',
                'attributes' => [
                    'name' => 'New Owner 1'
                ],
                'relationships' => [
                    'target' => [
                        'data' => ['type' => 'testapitargets', 'id' => '<toString(@target_2->id)>']
                    ],
                    'targets' => [
                        'data' => [
                            ['type' => 'testapitargets', 'id' => '<toString(@target_2->id)>']
                        ]
                    ],
                    'activityTestActivities' => [
                        'data' => [
                            ['type' => 'testapiactivities', 'id' => '<toString(@activity_2->id)>']
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'testapiowners'], $data, [], false);
        $this->assertResponseValidationErrors(
            [
                [
                    'title' => 'form constraint',
                    'detail' => 'The entity does not exist.',
                    'source' => ['pointer' => '/data/relationships/target/data']
                ],
                [
                    'title' => 'form constraint',
                    'detail' => 'The entity does not exist.',
                    'source' => ['pointer' => '/data/relationships/targets/data']
                ],
                [
                    'title' => 'form constraint',
                    'detail' => 'The entity does not exist.',
                    'source' => ['pointer' => '/data/relationships/activityTestActivities/data']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWhenEntityWithSpecifiedExternalIdAlreadyExist(): void
    {
        $data = [
            'data' => [
                'type' => 'testapiowners',
                'id' => 'ext_owner_1',
                'attributes' => [
                    'name' => 'New Owner 1',
                    'dbId' => 12345
                ],
                'relationships' => [
                    'target' => [
                        'data' => ['type' => 'testapitargets', 'id' => 'ext_target_1']
                    ],
                    'targets' => [
                        'data' => [
                            ['type' => 'testapitargets', 'id' => 'ext_target_1']
                        ]
                    ],
                    'activityTestActivities' => [
                        'data' => [
                            ['type' => 'testapiactivities', 'id' => 'ext_activity_1']
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'testapiowners'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title' => 'conflict constraint',
                'detail' => 'The entity already exists.'
            ],
            $response,
            Response::HTTP_CONFLICT
        );
    }

    public function testCreate(): void
    {
        $data = [
            'data' => [
                'type' => 'testapiowners',
                'id' => 'ext_new_owner_1',
                'attributes' => [
                    'name' => 'New Owner 1',
                    'dbId' => 12345
                ],
                'relationships' => [
                    'target' => [
                        'data' => ['type' => 'testapitargets', 'id' => 'ext_target_1']
                    ],
                    'targets' => [
                        'data' => [
                            ['type' => 'testapitargets', 'id' => 'ext_target_1']
                        ]
                    ],
                    'activityTestActivities' => [
                        'data' => [
                            ['type' => 'testapiactivities', 'id' => 'ext_activity_1']
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'testapiowners'], $data);
        $expectedData = $data;
        $expectedData['data']['attributes']['dbId'] = 'new';
        $expectedData = $this->updateResponseContent($expectedData, $response, 'dbId');
        $this->assertResponseContains($expectedData, $response);
        self::assertSame(
            $expectedData['data']['attributes']['dbId'],
            $this->getOwner('ext_new_owner_1')->id
        );
    }

    public function testCreateWithIncludedEntities(): void
    {
        $data = [
            'data' => [
                'type' => 'testapiowners',
                'id' => 'ext_new_owner_1',
                'attributes' => [
                    'name' => 'New Owner 1',
                    'dbId' => 12345
                ],
                'relationships' => [
                    'target' => [
                        'data' => ['type' => 'testapitargets', 'id' => 'ext_new_target_1']
                    ],
                    'targets' => [
                        'data' => [
                            ['type' => 'testapitargets', 'id' => 'ext_new_target_1']
                        ]
                    ],
                    'activityTestActivities' => [
                        'data' => [
                            ['type' => 'testapiactivities', 'id' => 'ext_new_activity_1']
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'testapitargets',
                    'id' => 'ext_new_target_1',
                    'attributes' => [
                        'name' => 'New Target 1',
                        'dbId' => 23456
                    ],
                    'relationships' => [
                        'activityTestActivities' => [
                            'data' => [
                                ['type' => 'testapiactivities', 'id' => 'ext_new_activity_1']
                            ]
                        ]
                    ]
                ],
                [
                    'type' => 'testapiactivities',
                    'id' => 'ext_new_activity_1',
                    'attributes' => [
                        'name' => 'New Activity 1',
                        'dbId' => 34567
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'testapiowners'], $data);
        $expectedData = $data;
        $expectedData['data']['attributes']['dbId'] = 'new';
        $expectedData['included'][0]['attributes']['dbId'] = 'new';
        $expectedData['included'][0]['attributes']['name'] = 'New Target 1 (customized)';
        $expectedData['included'][0]['relationships']['owners']['data'] = [
            ['type' => 'testapiowners', 'id' => 'ext_new_owner_1']
        ];
        $expectedData['included'][1]['attributes']['dbId'] = 'new';
        $expectedData['included'][1]['relationships']['activityTargets']['data'] = [
            ['type' => 'testapiowners', 'id' => 'ext_new_owner_1'],
            ['type' => 'testapitargets', 'id' => 'ext_new_target_1']
        ];
        $expectedData = $this->updateResponseContent($expectedData, $response, 'dbId');
        $this->assertResponseContains($expectedData, $response);
        self::assertSame(
            $expectedData['data']['attributes']['dbId'],
            $this->getOwner('ext_new_owner_1')->id
        );
        self::assertSame(
            $expectedData['included'][0]['attributes']['dbId'],
            $this->getTarget('ext_new_target_1')->getId()
        );
        self::assertSame(
            $expectedData['included'][1]['attributes']['dbId'],
            $this->getActivity('ext_new_activity_1')->id
        );
    }

    public function testTryToUpdateDbId(): void
    {
        $existingDbId = $this->getOwner('ext_owner_1')->id;
        $data = [
            'data' => [
                'type' => 'testapiowners',
                'id' => 'ext_owner_1',
                'attributes' => [
                    'dbId' => 12345
                ]
            ]
        ];
        $response = $this->patch(['entity' => 'testapiowners', 'id' => 'ext_owner_1'], $data);
        $expectedData = $data;
        $expectedData['data']['attributes']['dbId'] = 'new';
        $expectedData = $this->updateResponseContent($expectedData, $response, 'dbId');
        $this->assertResponseContains($expectedData, $response);
        self::assertSame($expectedData['data']['attributes']['dbId'], $existingDbId);
        self::assertSame($this->getOwner('ext_owner_1')->id, $existingDbId);
    }
}
