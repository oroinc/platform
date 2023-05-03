<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class IncludedEntityDuplicatesPrimaryEntityTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/included_entity_duplicates_primary_entity.yml'
        ]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'testapiorders'],
            ['include' => 'items.order']
        );
        self::assertEquals(
            $this->getResponseData([
                'data'     => [
                    [
                        'type'          => 'testapiorders',
                        'id'            => '<toString(@order1->id)>',
                        'attributes'    => ['poNumber' => 'ORDER1'],
                        'relationships' => [
                            'items'        => [
                                'data' => [
                                    ['type' => 'testapiorderlineitems', 'id' => '<toString(@order_line_item11->id)>'],
                                    ['type' => 'testapiorderlineitems', 'id' => '<toString(@order_line_item12->id)>']
                                ]
                            ],
                            'targetEntity' => ['data' => null]
                        ]
                    ],
                    [
                        'type'          => 'testapiorders',
                        'id'            => '<toString(@order2->id)>',
                        'attributes'    => ['poNumber' => 'ORDER2'],
                        'relationships' => [
                            'items'        => [
                                'data' => [
                                    ['type' => 'testapiorderlineitems', 'id' => '<toString(@order_line_item21->id)>']
                                ]
                            ],
                            'targetEntity' => ['data' => null]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'          => 'testapiorderlineitems',
                        'id'            => '<toString(@order_line_item11->id)>',
                        'attributes'    => ['quantity' => 1],
                        'relationships' => [
                            'order'   => ['data' => ['type' => 'testapiorders', 'id' => '<toString(@order1->id)>']],
                            'product' => ['data' => ['type' => 'testproducts', 'id' => '<toString(@product1->id)>']]
                        ]
                    ],
                    [
                        'type'          => 'testapiorderlineitems',
                        'id'            => '<toString(@order_line_item12->id)>',
                        'attributes'    => ['quantity' => 1],
                        'relationships' => [
                            'order'   => ['data' => ['type' => 'testapiorders', 'id' => '<toString(@order1->id)>']],
                            'product' => ['data' => ['type' => 'testproducts', 'id' => '<toString(@product2->id)>']]
                        ]
                    ],
                    [
                        'type'          => 'testapiorderlineitems',
                        'id'            => '<toString(@order_line_item21->id)>',
                        'attributes'    => ['quantity' => 1],
                        'relationships' => [
                            'order'   => ['data' => ['type' => 'testapiorders', 'id' => '<toString(@order2->id)>']],
                            'product' => ['data' => ['type' => 'testproducts', 'id' => '<toString(@product1->id)>']]
                        ]
                    ]
                ]
            ]),
            self::jsonToArray($response->getContent())
        );
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'testapiorders', 'id' => '<toString(@order1->id)>'],
            ['include' => 'items.order']
        );
        self::assertEquals(
            $this->getResponseData([
                'data'     => [
                    'type'          => 'testapiorders',
                    'id'            => '<toString(@order1->id)>',
                    'attributes'    => ['poNumber' => 'ORDER1'],
                    'relationships' => [
                        'items'        => [
                            'data' => [
                                ['type' => 'testapiorderlineitems', 'id' => '<toString(@order_line_item11->id)>'],
                                ['type' => 'testapiorderlineitems', 'id' => '<toString(@order_line_item12->id)>']
                            ]
                        ],
                        'targetEntity' => ['data' => null]
                    ]
                ],
                'included' => [
                    [
                        'type'          => 'testapiorderlineitems',
                        'id'            => '<toString(@order_line_item11->id)>',
                        'attributes'    => ['quantity' => 1],
                        'relationships' => [
                            'order'   => ['data' => ['type' => 'testapiorders', 'id' => '<toString(@order1->id)>']],
                            'product' => ['data' => ['type' => 'testproducts', 'id' => '<toString(@product1->id)>']]
                        ]
                    ],
                    [
                        'type'          => 'testapiorderlineitems',
                        'id'            => '<toString(@order_line_item12->id)>',
                        'attributes'    => ['quantity' => 1],
                        'relationships' => [
                            'order'   => ['data' => ['type' => 'testapiorders', 'id' => '<toString(@order1->id)>']],
                            'product' => ['data' => ['type' => 'testproducts', 'id' => '<toString(@product2->id)>']]
                        ]
                    ]
                ]
            ]),
            self::jsonToArray($response->getContent())
        );
    }
}
