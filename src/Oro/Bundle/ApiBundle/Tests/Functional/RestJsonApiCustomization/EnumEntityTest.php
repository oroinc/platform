<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiCustomization;

use Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadEnumsData;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class EnumEntityTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadEnumsData::class,
            '@OroApiBundle/Tests/Functional/DataFixtures/custom_entities.yml'
        ]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'testapienum1']);
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapienum1',
                        'id'         => '<toString(@enum1_0->id)>',
                        'attributes' => [
                            'name'     => 'Item 0',
                            'priority' => -1,
                            'default'  => true
                        ]
                    ],
                    [
                        'type'       => 'testapienum1',
                        'id'         => '<toString(@enum1_1->id)>',
                        'attributes' => [
                            'name'     => 'Item 1',
                            'priority' => 0,
                            'default'  => false
                        ]
                    ],
                    [
                        'type'       => 'testapienum1',
                        'id'         => '<toString(@enum1_2->id)>',
                        'attributes' => [
                            'name'     => 'Item 2',
                            'priority' => 1,
                            'default'  => false
                        ]
                    ],
                    [
                        'type'       => 'testapienum1',
                        'id'         => '<toString(@enum1_3->id)>',
                        'attributes' => [
                            'name'     => 'Item 3',
                            'priority' => 2,
                            'default'  => false
                        ]
                    ],
                    [
                        'type'       => 'testapienum1',
                        'id'         => '<toString(@enum1_4->id)>',
                        'attributes' => [
                            'name'     => 'Item 4',
                            'priority' => 3,
                            'default'  => false
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredAndSortedByPriority(): void
    {
        $response = $this->cget(
            ['entity' => 'testapienum1'],
            ['filter[priority]' => '1..3', 'sort' => '-priority']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'testapienum1',
                        'id'         => '<toString(@enum1_4->id)>',
                        'attributes' => [
                            'name'     => 'Item 4',
                            'priority' => 3,
                            'default'  => false
                        ]
                    ],
                    [
                        'type'       => 'testapienum1',
                        'id'         => '<toString(@enum1_3->id)>',
                        'attributes' => [
                            'name'     => 'Item 3',
                            'priority' => 2,
                            'default'  => false
                        ]
                    ],
                    [
                        'type'       => 'testapienum1',
                        'id'         => '<toString(@enum1_2->id)>',
                        'attributes' => [
                            'name'     => 'Item 2',
                            'priority' => 1,
                            'default'  => false
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGet(): void
    {
        $response = $this->get(['entity' => 'testapienum1', 'id' => '<toString(@enum1_1->id)>']);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'testapienum1',
                    'id'         => '<toString(@enum1_1->id)>',
                    'attributes' => [
                        'name'     => 'Item 1',
                        'priority' => 0,
                        'default'  => false
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'testapienum1', 'id' => 'new_status'],
            ['data' => ['type' => 'testapienum1', 'id' => 'new_status']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'testapienum1', 'id' => '<toString(@enum1_1->id)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'testapienum1'],
            ['filter[id]' => '<toString(@enum1_1->id)>'],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetOptionsForList(): void
    {
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => 'testapienum1']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testOptionsForItem(): void
    {
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => 'testapienum1', 'id' => '<toString(@enum1_1->id)>']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }
}
