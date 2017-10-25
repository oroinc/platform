<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Extend\Entity\TestApiE1 as TestEntity1;
use Extend\Entity\TestApiE2 as TestEntity2;
use Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadEnumsData;

/**
 * @dbIsolationPerTest
 */
class RestJsonApiCustomEntityTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            LoadEnumsData::class,
            '@OroApiBundle/Tests/Functional/DataFixtures/custom_entities.yml'
        ]);
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'testapientity1', 'id' => '<toString(@entity1_1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapientity1',
                    'id'            => '<toString(@entity1_1->id)>',
                    'attributes'    => [
                        'name' => 'Entity 1 (1)'
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
                        'uniM2O'         => [
                            'data' => ['type' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>']
                        ],
                        'biM2O'          => [
                            'data' => ['type' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>']
                        ],
                        'uniM2M'         => [
                            'data' => [
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>'],
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_2->id)>']
                            ]
                        ],
                        'default_uniM2M' => [
                            'data' => ['type' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>']
                        ],
                        'uniM2MnD'       => [
                            'data' => [
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>'],
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_2->id)>']
                            ]
                        ],
                        'biM2M'          => [
                            'data' => [
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>'],
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_2->id)>']
                            ]
                        ],
                        'default_biM2M'  => [
                            'data' => ['type' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>']
                        ],
                        'biM2MnD'        => [
                            'data' => [
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>'],
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_2->id)>']
                            ]
                        ],
                        'uniO2M'         => [
                            'data' => [
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>'],
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_2->id)>']
                            ]
                        ],
                        'default_uniO2M' => [
                            'data' => ['type' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>']
                        ],
                        'uniO2MnD'       => [
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
                        ],
                        'default_biO2M'  => [
                            'data' => ['type' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>']
                        ],
                        'biO2MnD'        => [
                            'data' => [
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>'],
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_2->id)>']
                            ]
                        ],
                    ]
                ]
            ],
            $response
        );
    }
}
