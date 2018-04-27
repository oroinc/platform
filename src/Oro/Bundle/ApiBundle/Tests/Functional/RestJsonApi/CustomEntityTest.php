<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Extend\Entity\TestApiE1 as TestEntity1;
use Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadEnumsData;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @dbIsolationPerTest
 */
class CustomEntityTest extends RestJsonApiTestCase
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

    protected function renameTestEntity1Fields()
    {
        $this->appendEntityConfig(
            TestEntity1::class,
            [
                'fields' => [
                    'renamedName'           => ['property_path' => 'name'],
                    'renamedEnumField'      => ['property_path' => 'enumField'],
                    'renamedMultiEnumField' => ['property_path' => 'multiEnumField'],
                    'renamedUniM2O'         => ['property_path' => 'uniM2O'],
                    'renamedBiM2O'          => ['property_path' => 'biM2O'],
                    'renamedUniM2M'         => ['property_path' => 'uniM2M'],
                    'renamedDefaultUniM2M'  => ['property_path' => 'default_uniM2M'],
                    'renamedUniM2MnD'       => ['property_path' => 'uniM2MnD'],
                    'renamedBiM2M'          => ['property_path' => 'biM2M'],
                    'renamedDefaultBiM2M'   => ['property_path' => 'default_biM2M'],
                    'renamedBiM2MnD'        => ['property_path' => 'biM2MnD'],
                    'renamedUniO2M'         => ['property_path' => 'uniO2M'],
                    'renamedDefaultUniO2M'  => ['property_path' => 'default_uniO2M'],
                    'renamedUniO2MnD'       => ['property_path' => 'uniO2MnD'],
                    'renamedBiO2M'          => ['property_path' => 'biO2M'],
                    'renamedDefaultBiO2M'   => ['property_path' => 'default_biO2M'],
                    'renamedBiO2MnD'        => ['property_path' => 'biO2MnD'],
                ]
            ],
            true
        );
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

    public function testGetForRenamedFields()
    {
        $this->renameTestEntity1Fields();
        $response = $this->get(
            ['entity' => 'testapientity1', 'id' => '<toString(@entity1_1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'testapientity1',
                    'id'            => '<toString(@entity1_1->id)>',
                    'attributes'    => [
                        'renamedName' => 'Entity 1 (1)'
                    ],
                    'relationships' => [
                        'renamedEnumField'      => [
                            'data' => ['type' => 'testapienum1', 'id' => '<toString(@enum1_1->id)>']
                        ],
                        'renamedMultiEnumField' => [
                            'data' => [
                                ['type' => 'testapienum2', 'id' => '<toString(@enum2_1->id)>'],
                                ['type' => 'testapienum2', 'id' => '<toString(@enum2_2->id)>']
                            ]
                        ],
                        'renamedUniM2O'         => [
                            'data' => ['type' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>']
                        ],
                        'renamedBiM2O'          => [
                            'data' => ['type' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>']
                        ],
                        'renamedUniM2M'         => [
                            'data' => [
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>'],
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_2->id)>']
                            ]
                        ],
                        'renamedDefaultUniM2M'  => [
                            'data' => ['type' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>']
                        ],
                        'renamedUniM2MnD'       => [
                            'data' => [
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>'],
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_2->id)>']
                            ]
                        ],
                        'renamedBiM2M'          => [
                            'data' => [
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>'],
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_2->id)>']
                            ]
                        ],
                        'renamedDefaultBiM2M'   => [
                            'data' => ['type' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>']
                        ],
                        'renamedBiM2MnD'        => [
                            'data' => [
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>'],
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_2->id)>']
                            ]
                        ],
                        'renamedUniO2M'         => [
                            'data' => [
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>'],
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_2->id)>']
                            ]
                        ],
                        'renamedDefaultUniO2M'  => [
                            'data' => ['type' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>']
                        ],
                        'renamedUniO2MnD'       => [
                            'data' => [
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>'],
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_2->id)>']
                            ]
                        ],
                        'renamedBiO2M'          => [
                            'data' => [
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>'],
                                ['type' => 'testapientity2', 'id' => '<toString(@entity2_2->id)>']
                            ]
                        ],
                        'renamedDefaultBiO2M'   => [
                            'data' => ['type' => 'testapientity2', 'id' => '<toString(@entity2_1->id)>']
                        ],
                        'renamedBiO2MnD'        => [
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
