<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Extend\Entity\TestApiE1 as TestEntity1;
use Extend\Entity\TestApiE2 as TestEntity2;
use Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadEnumsData;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @dbIsolationPerTest
 */
class CustomizeLoadedDataTest extends RestJsonApiTestCase
{
    private const FIELDS = [
        'fields[testapientity1]' => 'name,computedName,computedIds,enumField,multiEnumField,biM2O,biM2M,biO2M',
        'fields[testapientity2]' => 'name,computedName,computedIds,biM2OOwners,biM2MOwners,biO2MOwner'
    ];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            LoadEnumsData::class,
            '@OroApiBundle/Tests/Functional/DataFixtures/customize_loaded_data.yml'
        ]);

        $this->appendEntityConfig(
            TestEntity1::class,
            [
                'fields' => [
                    'computedName' => ['data_type' => 'string'],
                    'computedIds'  => ['data_type' => 'string']
                ]
            ]
        );
        $this->appendEntityConfig(
            TestEntity2::class,
            [
                'fields' => [
                    'computedName' => ['data_type' => 'string'],
                    'computedIds'  => ['data_type' => 'string']
                ]
            ]
        );
    }

    /**
     * @param string $entityClass
     * @param int[]  $ids
     *
     * @return string
     */
    private function getComputedIds($entityClass, ...$ids)
    {
        sort($ids);

        return sprintf('[%s] (%s)', implode(',', $ids), $entityClass);
    }

    public function testGetForTestEntity1()
    {
        $response = $this->get(
            ['entity' => 'testapientity1', 'id' => '<toString(@entity1_1->id)>'],
            self::FIELDS
        );

        $computedId = $this->getComputedIds(
            TestEntity1::class,
            $this->getReference('entity1_1')->getId()
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapientity1',
                    'id'            => '<toString(@entity1_1->id)>',
                    'attributes'    => [
                        'name'         => 'Entity 1_1',
                        'computedName' => 'Entity 1_1 (computed)',
                        'computedIds'  => $computedId
                    ],
                    'relationships' => [
                        'enumField'      => [
                            'data' => ['type' => 'testapienum1', 'id' => '<toString(@enum1_1->id)>']
                        ],
                        'multiEnumField' => [
                            'data' => [
                                ['type' => 'testapienum2', 'id' => '<toString(@enum2_1->id)>'],
                                ['type' => 'testapienum2', 'id' => '<toString(@enum2_2->id)>']
                            ]
                        ],
                        'biM2O'          => [
                            'data' => ['type' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>']
                        ],
                        'biM2M'          => [
                            'data' => [
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>'],
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_2->id)>']
                            ]
                        ],
                        'biO2M'          => [
                            'data' => [
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>'],
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_2->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetForTestEntity2()
    {
        $response = $this->get(
            ['entity' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>'],
            self::FIELDS
        );

        $computedId = $this->getComputedIds(
            TestEntity2::class,
            $this->getReference('entity2_1')->getId()
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapientity2',
                    'id'            => '<toString(@entity2_1->id)>',
                    'attributes'    => [
                        'name'         => 'Entity 2_1',
                        'computedName' => 'Entity 2_1 (computed)',
                        'computedIds'  => $computedId
                    ],
                    'relationships' => [
                        'biM2OOwners' => [
                            'data' => [
                                ['type' => 'testapientity1', 'id' => '<toString(@entity1_1->id)>'],
                                ['type' => 'testapientity1', 'id' => '<toString(@entity1_2->id)>']
                            ]
                        ],
                        'biM2MOwners' => [
                            'data' => [
                                ['type' => 'testapientity1', 'id' => '<toString(@entity1_1->id)>'],
                                ['type' => 'testapientity1', 'id' => '<toString(@entity1_2->id)>']
                            ]
                        ],
                        'biO2MOwner'  => [
                            'data' => ['type' => 'testapientity1', 'id' => '<toString(@entity1_1->id)>']
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetWithIncludeForTestEntity1()
    {
        $response = $this->get(
            ['entity' => 'testapientity1', 'id' => '<toString(@entity1_1->id)>'],
            array_merge(self::FIELDS, ['include' => 'biM2M'])
        );

        $computedId = $this->getComputedIds(
            TestEntity1::class,
            $this->getReference('entity1_1')->getId()
        );
        $computedIdForBiM2M = $this->getComputedIds(
            TestEntity2::class,
            $this->getReference('entity2_1')->getId(),
            $this->getReference('entity2_2')->getId()
        );
        $this->assertResponseContains(
            [
                'data'     => [
                    'type'       => 'testapientity1',
                    'id'         => '<toString(@entity1_1->id)>',
                    'attributes' => [
                        'name'         => 'Entity 1_1',
                        'computedName' => 'Entity 1_1 (computed)',
                        'computedIds'  => $computedId
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'testapientity2',
                        'id'         => '<toString(@entity2_1->id)>',
                        'attributes' => [
                            'computedIds' => $computedIdForBiM2M
                        ]
                    ],
                    [
                        'type'       => 'testapientity2',
                        'id'         => '<toString(@entity2_2->id)>',
                        'attributes' => [
                            'computedIds' => $computedIdForBiM2M
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetWithIncludeForTestEntity2()
    {
        $response = $this->get(
            ['entity' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>'],
            array_merge(self::FIELDS, ['include' => 'biM2MOwners'])
        );

        $computedId = $this->getComputedIds(
            TestEntity2::class,
            $this->getReference('entity2_1')->getId()
        );
        $computedIdForBiM2MOwners = $this->getComputedIds(
            TestEntity1::class,
            $this->getReference('entity1_1')->getId(),
            $this->getReference('entity1_2')->getId()
        );
        $this->assertResponseContains(
            [
                'data'     => [
                    'type'       => 'testapientity2',
                    'id'         => '<toString(@entity2_1->id)>',
                    'attributes' => [
                        'name'         => 'Entity 2_1',
                        'computedName' => 'Entity 2_1 (computed)',
                        'computedIds'  => $computedId
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'testapientity1',
                        'id'         => '<toString(@entity1_1->id)>',
                        'attributes' => [
                            'computedIds' => $computedIdForBiM2MOwners
                        ]
                    ],
                    [
                        'type'       => 'testapientity1',
                        'id'         => '<toString(@entity1_2->id)>',
                        'attributes' => [
                            'computedIds' => $computedIdForBiM2MOwners
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListForTestEntity1()
    {
        $response = $this->cget(
            ['entity' => 'testapientity1'],
            self::FIELDS
        );

        $computedId = $this->getComputedIds(
            TestEntity1::class,
            $this->getReference('entity1_1')->getId(),
            $this->getReference('entity1_2')->getId(),
            $this->getReference('entity1_3')->getId(),
            $this->getReference('entity1_4')->getId()
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapientity1',
                        'id'         => '<toString(@entity1_1->id)>',
                        'attributes' => [
                            'name'         => 'Entity 1_1',
                            'computedName' => 'Entity 1_1 (computed)',
                            'computedIds'  => $computedId
                        ]
                    ],
                    [
                        'type'       => 'testapientity1',
                        'id'         => '<toString(@entity1_2->id)>',
                        'attributes' => [
                            'name'         => 'Entity 1_2',
                            'computedName' => 'Entity 1_2 (computed)',
                            'computedIds'  => $computedId
                        ]
                    ],
                    [
                        'type'       => 'testapientity1',
                        'id'         => '<toString(@entity1_3->id)>',
                        'attributes' => [
                            'name'         => 'Entity 1_3',
                            'computedName' => 'Entity 1_3 (computed)',
                            'computedIds'  => $computedId
                        ]
                    ],
                    [
                        'type'       => 'testapientity1',
                        'id'         => '<toString(@entity1_4->id)>',
                        'attributes' => [
                            'name'         => 'Entity 1_4',
                            'computedName' => 'Entity 1_4 (computed)',
                            'computedIds'  => $computedId
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListForTestEntity2()
    {
        $response = $this->cget(
            ['entity' => 'testapientity2'],
            self::FIELDS
        );

        $computedId = $this->getComputedIds(
            TestEntity2::class,
            $this->getReference('entity2_1')->getId(),
            $this->getReference('entity2_2')->getId(),
            $this->getReference('entity2_3')->getId(),
            $this->getReference('entity2_4')->getId()
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapientity2',
                        'id'         => '<toString(@entity2_1->id)>',
                        'attributes' => [
                            'name'         => 'Entity 2_1',
                            'computedName' => 'Entity 2_1 (computed)',
                            'computedIds'  => $computedId
                        ]
                    ],
                    [
                        'type'       => 'testapientity2',
                        'id'         => '<toString(@entity2_2->id)>',
                        'attributes' => [
                            'name'         => 'Entity 2_2',
                            'computedName' => 'Entity 2_2 (computed)',
                            'computedIds'  => $computedId
                        ]
                    ],
                    [
                        'type'       => 'testapientity2',
                        'id'         => '<toString(@entity2_3->id)>',
                        'attributes' => [
                            'name'         => 'Entity 2_3',
                            'computedName' => 'Entity 2_3 (computed)',
                            'computedIds'  => $computedId
                        ]
                    ],
                    [
                        'type'       => 'testapientity2',
                        'id'         => '<toString(@entity2_4->id)>',
                        'attributes' => [
                            'name'         => 'Entity 2_4',
                            'computedName' => 'Entity 2_4 (computed)',
                            'computedIds'  => $computedId
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceForManyToManyOwnerSide()
    {
        $response = $this->getSubresource(
            ['entity' => 'testapientity1', 'id' => '<toString(@entity1_1->id)>', 'association' => 'biM2M'],
            self::FIELDS
        );

        $computedId = $this->getComputedIds(
            TestEntity2::class,
            $this->getReference('entity2_1')->getId(),
            $this->getReference('entity2_2')->getId()
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapientity2',
                        'id'         => '<toString(@entity2_1->id)>',
                        'attributes' => [
                            'name'         => 'Entity 2_1',
                            'computedName' => 'Entity 2_1 (computed)',
                            'computedIds'  => $computedId
                        ]
                    ],
                    [
                        'type'       => 'testapientity2',
                        'id'         => '<toString(@entity2_2->id)>',
                        'attributes' => [
                            'name'         => 'Entity 2_2',
                            'computedName' => 'Entity 2_2 (computed)',
                            'computedIds'  => $computedId
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceForManyToManyInverseSide()
    {
        $response = $this->getSubresource(
            ['entity' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>', 'association' => 'biM2MOwners'],
            self::FIELDS
        );

        $computedId = $this->getComputedIds(
            TestEntity1::class,
            $this->getReference('entity1_1')->getId(),
            $this->getReference('entity1_2')->getId()
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapientity1',
                        'id'         => '<toString(@entity1_1->id)>',
                        'attributes' => [
                            'name'         => 'Entity 1_1',
                            'computedName' => 'Entity 1_1 (computed)',
                            'computedIds'  => $computedId
                        ]
                    ],
                    [
                        'type'       => 'testapientity1',
                        'id'         => '<toString(@entity1_2->id)>',
                        'attributes' => [
                            'name'         => 'Entity 1_2',
                            'computedName' => 'Entity 1_2 (computed)',
                            'computedIds'  => $computedId
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceForManyToManyWithInclude()
    {
        $response = $this->getSubresource(
            ['entity' => 'testapientity1', 'id' => '<toString(@entity1_1->id)>', 'association' => 'biM2M'],
            array_merge(self::FIELDS, ['include' => 'biM2OOwners'])
        );

        $computedId = $this->getComputedIds(
            TestEntity2::class,
            $this->getReference('entity2_1')->getId(),
            $this->getReference('entity2_2')->getId()
        );
        $computedIdForBiM2OOwners = $this->getComputedIds(
            TestEntity1::class,
            $this->getReference('entity1_1')->getId(),
            $this->getReference('entity1_2')->getId()
        );
        $this->assertResponseContains(
            [
                'data'     => [
                    [
                        'type'       => 'testapientity2',
                        'id'         => '<toString(@entity2_1->id)>',
                        'attributes' => [
                            'name'         => 'Entity 2_1',
                            'computedName' => 'Entity 2_1 (computed)',
                            'computedIds'  => $computedId
                        ]
                    ],
                    [
                        'type'       => 'testapientity2',
                        'id'         => '<toString(@entity2_2->id)>',
                        'attributes' => [
                            'name'         => 'Entity 2_2',
                            'computedName' => 'Entity 2_2 (computed)',
                            'computedIds'  => $computedId
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'testapientity1',
                        'id'         => '<toString(@entity1_1->id)>',
                        'attributes' => [
                            'computedIds' => $computedIdForBiM2OOwners
                        ]
                    ],
                    [
                        'type'       => 'testapientity1',
                        'id'         => '<toString(@entity1_2->id)>',
                        'attributes' => [
                            'computedIds' => $computedIdForBiM2OOwners
                        ]
                    ]
                ]
            ],
            $response
        );
    }
}
