<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\ExtIdRestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @dbIsolationPerTest
 */
class RegularApiWithCustomExternalIdTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/ext_id_entities.yml'
        ]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'testapiowners']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'testapiowners',
                        'id' => '<toString(@owner_1->id)>',
                        'attributes' => [
                            'name' => 'Owner 1',
                            'externalId' => 'ext_owner_1'
                        ],
                        'relationships' => [
                            'target' => [
                                'data' => ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>']
                            ],
                            'targets' => [
                                'data' => [
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>'],
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_2->id)>']
                                ]
                            ],
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_1->id)>'],
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_2->id)>'],
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_3->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiowners',
                        'id' => '<toString(@owner_2->id)>',
                        'attributes' => [
                            'name' => 'Owner 2',
                            'externalId' => 'ext_owner_2'
                        ],
                        'relationships' => [
                            'target' => [
                                'data' => ['type' => 'testapitargets', 'id' => '<toString(@target_4->id)>']
                            ],
                            'targets' => [
                                'data' => [
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>'],
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_4->id)>']
                                ]
                            ],
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_2->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiowners',
                        'id' => '<toString(@owner_3->id)>',
                        'attributes' => [
                            'name' => 'Owner 3',
                            'externalId' => null
                        ],
                        'relationships' => [
                            'target' => [
                                'data' => ['type' => 'testapitargets', 'id' => '<toString(@target_3->id)>']
                            ],
                            'targets' => [
                                'data' => [
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_3->id)>']
                                ]
                            ],
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_1->id)>'],
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_3->id)>']
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
                        'id' => '<toString(@owner_1->id)>',
                        'attributes' => [
                            'name' => 'Owner 1',
                            'externalId' => 'ext_owner_1'
                        ],
                        'relationships' => [
                            'target' => [
                                'data' => ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>']
                            ],
                            'targets' => [
                                'data' => [
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>'],
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_2->id)>']
                                ]
                            ],
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_1->id)>'],
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_2->id)>'],
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_3->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiowners',
                        'id' => '<toString(@owner_2->id)>',
                        'attributes' => [
                            'name' => 'Owner 2',
                            'externalId' => 'ext_owner_2'
                        ],
                        'relationships' => [
                            'target' => [
                                'data' => ['type' => 'testapitargets', 'id' => '<toString(@target_4->id)>']
                            ],
                            'targets' => [
                                'data' => [
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>'],
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_4->id)>']
                                ]
                            ],
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_2->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiowners',
                        'id' => '<toString(@owner_3->id)>',
                        'attributes' => [
                            'name' => 'Owner 3',
                            'externalId' => null
                        ],
                        'relationships' => [
                            'target' => [
                                'data' => ['type' => 'testapitargets', 'id' => '<toString(@target_3->id)>']
                            ],
                            'targets' => [
                                'data' => [
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_3->id)>']
                                ]
                            ],
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_1->id)>'],
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_3->id)>']
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'testapitargets',
                        'id' => '<toString(@target_1->id)>',
                        'attributes' => [
                            'name' => 'Target 1 (customized)',
                            'externalId' => 'ext_target_1'
                        ],
                        'relationships' => [
                            'owners' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_1->id)>'],
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_2->id)>']
                                ]
                            ],
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_1->id)>'],
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_2->id)>'],
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_3->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapitargets',
                        'id' => '<toString(@target_2->id)>',
                        'attributes' => [
                            'name' => 'Target 2 (customized)',
                            'externalId' => 'ext_target_2'
                        ],
                        'relationships' => [
                            'owners' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_1->id)>']
                                ]
                            ],
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_2->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiactivities',
                        'id' => '<toString(@activity_1->id)>',
                        'attributes' => [
                            'name' => 'Activity 1',
                            'externalId' => 'ext_activity_1'
                        ],
                        'relationships' => [
                            'activityTargets' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_1->id)>'],
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_3->id)>'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_1->id)>'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_3->id)>'],
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>'],
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_4->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiactivities',
                        'id' => '<toString(@activity_2->id)>',
                        'attributes' => [
                            'name' => 'Activity 2',
                            'externalId' => 'ext_activity_2'
                        ],
                        'relationships' => [
                            'activityTargets' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_1->id)>'],
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_2->id)>'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_1->id)>'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_2->id)>'],
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>'],
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_2->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiactivities',
                        'id' => '<toString(@activity_3->id)>',
                        'attributes' => [
                            'name' => 'Activity 3',
                            'externalId' => null
                        ],
                        'relationships' => [
                            'activityTargets' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_1->id)>'],
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_3->id)>'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_1->id)>'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_3->id)>'],
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>'],
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_4->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapitargets',
                        'id' => '<toString(@target_4->id)>',
                        'attributes' => [
                            'name' => 'Target 4 (customized)',
                            'externalId' => null
                        ],
                        'relationships' => [
                            'owners' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_2->id)>']
                                ]
                            ],
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_1->id)>'],
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_3->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapitargets',
                        'id' => '<toString(@target_3->id)>',
                        'attributes' => [
                            'name' => 'Target 3 (customized)',
                            'externalId' => 'ext_target_3'
                        ],
                        'relationships' => [
                            'owners' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_3->id)>']
                                ]
                            ],
                            'activityTestActivities' => [
                                'data' => []
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
                        'id' => '<toString(@activity_1->id)>',
                        'attributes' => [
                            'name' => 'Activity 1',
                            'externalId' => 'ext_activity_1'
                        ],
                        'relationships' => [
                            'activityTargets' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_1->id)>'],
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_3->id)>'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_1->id)>'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_3->id)>'],
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>'],
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_4->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiactivities',
                        'id' => '<toString(@activity_2->id)>',
                        'attributes' => [
                            'name' => 'Activity 2',
                            'externalId' => 'ext_activity_2'
                        ],
                        'relationships' => [
                            'activityTargets' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_1->id)>'],
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_2->id)>'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_1->id)>'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_2->id)>'],
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>'],
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_2->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiactivities',
                        'id' => '<toString(@activity_3->id)>',
                        'attributes' => [
                            'name' => 'Activity 3',
                            'externalId' => null
                        ],
                        'relationships' => [
                            'activityTargets' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_1->id)>'],
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_3->id)>'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_1->id)>'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_3->id)>'],
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>'],
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_4->id)>']
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
                        'id' => '<toString(@activity_1->id)>',
                        'attributes' => [
                            'name' => 'Activity 1',
                            'externalId' => 'ext_activity_1'
                        ],
                        'relationships' => [
                            'activityTargets' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_1->id)>'],
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_3->id)>'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_1->id)>'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_3->id)>'],
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>'],
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_4->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiactivities',
                        'id' => '<toString(@activity_2->id)>',
                        'attributes' => [
                            'name' => 'Activity 2',
                            'externalId' => 'ext_activity_2'
                        ],
                        'relationships' => [
                            'activityTargets' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_1->id)>'],
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_2->id)>'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_1->id)>'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_2->id)>'],
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>'],
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_2->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiactivities',
                        'id' => '<toString(@activity_3->id)>',
                        'attributes' => [
                            'name' => 'Activity 3',
                            'externalId' => null
                        ],
                        'relationships' => [
                            'activityTargets' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_1->id)>'],
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_3->id)>'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_1->id)>'],
                                    ['type' => 'testapiactivitytargets', 'id' => '<toString(@activity_target_3->id)>'],
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>'],
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_4->id)>']
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'testapiowners',
                        'id' => '<toString(@owner_1->id)>',
                        'attributes' => [
                            'name' => 'Owner 1',
                            'externalId' => 'ext_owner_1'
                        ],
                        'relationships' => [
                            'target' => [
                                'data' => ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>']
                            ],
                            'targets' => [
                                'data' => [
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>'],
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_2->id)>']
                                ]
                            ],
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_1->id)>'],
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_2->id)>'],
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_3->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiowners',
                        'id' => '<toString(@owner_3->id)>',
                        'attributes' => [
                            'name' => 'Owner 3',
                            'externalId' => null
                        ],
                        'relationships' => [
                            'target' => [
                                'data' => ['type' => 'testapitargets', 'id' => '<toString(@target_3->id)>']
                            ],
                            'targets' => [
                                'data' => [
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_3->id)>']
                                ]
                            ],
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_1->id)>'],
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_3->id)>']
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
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_1->id)>'],
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_2->id)>'],
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_3->id)>']
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
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_1->id)>'],
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_3->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapitargets',
                        'id' => '<toString(@target_1->id)>',
                        'attributes' => [
                            'name' => 'Target 1 (customized)',
                            'externalId' => 'ext_target_1'
                        ],
                        'relationships' => [
                            'owners' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_1->id)>'],
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_2->id)>']
                                ]
                            ],
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_1->id)>'],
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_2->id)>'],
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_3->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapitargets',
                        'id' => '<toString(@target_4->id)>',
                        'attributes' => [
                            'name' => 'Target 4 (customized)',
                            'externalId' => null
                        ],
                        'relationships' => [
                            'owners' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_2->id)>']
                                ]
                            ],
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_1->id)>'],
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_3->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapiowners',
                        'id' => '<toString(@owner_2->id)>',
                        'attributes' => [
                            'name' => 'Owner 2',
                            'externalId' => 'ext_owner_2'
                        ],
                        'relationships' => [
                            'target' => [
                                'data' => ['type' => 'testapitargets', 'id' => '<toString(@target_4->id)>']
                            ],
                            'targets' => [
                                'data' => [
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>'],
                                    ['type' => 'testapitargets', 'id' => '<toString(@target_4->id)>']
                                ]
                            ],
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_2->id)>']
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
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_2->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'testapitargets',
                        'id' => '<toString(@target_2->id)>',
                        'attributes' => [
                            'name' => 'Target 2 (customized)',
                            'externalId' => 'ext_target_2'
                        ],
                        'relationships' => [
                            'owners' => [
                                'data' => [
                                    ['type' => 'testapiowners', 'id' => '<toString(@owner_1->id)>']
                                ]
                            ],
                            'activityTestActivities' => [
                                'data' => [
                                    ['type' => 'testapiactivities', 'id' => '<toString(@activity_2->id)>']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testCreate(): void
    {
        $data = [
            'data' => [
                'type' => 'testapiowners',
                'id' => 'new_owner_1',
                'attributes' => [
                    'name' => 'New Owner 1',
                    'externalId' => 'ext_new_owner_1'
                ],
                'relationships' => [
                    'target' => [
                        'data' => ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>']
                    ],
                    'targets' => [
                        'data' => [
                            ['type' => 'testapitargets', 'id' => '<toString(@target_1->id)>']
                        ]
                    ],
                    'activityTestActivities' => [
                        'data' => [
                            ['type' => 'testapiactivities', 'id' => '<toString(@activity_1->id)>']
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'testapiowners'], $data);
        $expectedData = $data;
        $expectedData['data']['id'] = 'new';
        $expectedData = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testCreateWithIncludedEntities(): void
    {
        $data = [
            'data' => [
                'type' => 'testapiowners',
                'id' => 'new_owner_1',
                'attributes' => [
                    'name' => 'New Owner 1',
                    'externalId' => 'ext_new_owner_1'
                ],
                'relationships' => [
                    'target' => [
                        'data' => ['type' => 'testapitargets', 'id' => 'new_target_1']
                    ],
                    'targets' => [
                        'data' => [
                            ['type' => 'testapitargets', 'id' => 'new_target_1']
                        ]
                    ],
                    'activityTestActivities' => [
                        'data' => [
                            ['type' => 'testapiactivities', 'id' => 'new_activity_1']
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'testapitargets',
                    'id' => 'new_target_1',
                    'attributes' => [
                        'name' => 'New Target 1',
                        'externalId' => 'ext_new_target_1'
                    ],
                    'relationships' => [
                        'activityTestActivities' => [
                            'data' => [
                                ['type' => 'testapiactivities', 'id' => 'new_activity_1']
                            ]
                        ]
                    ]
                ],
                [
                    'type' => 'testapiactivities',
                    'id' => 'new_activity_1',
                    'attributes' => [
                        'name' => 'New Activity 1',
                        'externalId' => 'ext_new_activity_1'
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'testapiowners'], $data);
        $expectedData = $data;
        $expectedData['data']['id'] = 'new';
        $expectedData['data']['relationships']['target']['data']['id'] = 'new';
        $expectedData['data']['relationships']['targets']['data'][0]['id'] = 'new';
        $expectedData['data']['relationships']['activityTestActivities']['data'][0]['id'] = 'new';
        $expectedData['included'][0]['id'] = 'new';
        $expectedData['included'][0]['attributes']['name'] = 'New Target 1 (customized)';
        $expectedData['included'][0]['relationships']['owners']['data'] = [
            ['type' => 'testapiowners', 'id' => 'new']
        ];
        $expectedData['included'][0]['relationships']['activityTestActivities']['data'][0]['id'] = 'new';
        $expectedData['included'][1]['id'] = 'new';
        $expectedData = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testTryToUpdateExternalIdWhenEntityWithSpecifiedExternalIdAlreadyExist(): void
    {
        $response = $this->patch(
            ['entity' => 'testapiowners', 'id' => '<toString(@owner_1->id)>'],
            [
                'data' => [
                    'type' => 'testapiowners',
                    'id' => '<toString(@owner_1->id)>',
                    'attributes' => [
                        'externalId' => 'ext_owner_2'
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'unique entity constraint',
                'detail' => 'Value for field "oro.api.tests.functional.environment.entity.testowner.external_id.label"'
                    . ' must be unique'
            ],
            $response
        );
    }
}
