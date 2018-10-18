<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestOrder;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestOrderLineItem;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;

/**
 * @dbIsolationPerTest
 */
class PendingValidationTest extends RestJsonApiTestCase
{
    public function testErrorPathIfIncludedEntityIsNotValid()
    {
        $orderType = $this->getEntityType(TestOrder::class);
        $orderLineItemType = $this->getEntityType(TestOrderLineItem::class);

        $data = [
            'data'     => [
                'type'          => $orderType,
                'attributes'    => [
                    'poNumber' => null
                ],
                'relationships' => [
                    'items' => [
                        'data' => [
                            ['type' => $orderLineItemType, 'id' => 'item1'],
                            ['type' => $orderLineItemType, 'id' => 'item2'],
                            ['type' => $orderLineItemType, 'id' => 'item3'],
                            ['type' => $orderLineItemType, 'id' => 'item4']
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => $orderLineItemType,
                    'id'         => 'item1',
                    'attributes' => [
                        'quantity' => null
                    ]
                ],
                [
                    'type'       => $orderLineItemType,
                    'id'         => 'item2',
                    'attributes' => [
                        'quantity' => 1
                    ]
                ],
                [
                    'type'       => $orderLineItemType,
                    'id'         => 'item3',
                    'attributes' => [
                        'quantity' => null
                    ]
                ],
                [
                    'type'       => $orderLineItemType,
                    'id'         => 'item4',
                    'attributes' => [
                        'quantity' => 1
                    ]
                ]
            ]
        ];

        $response = $this->post(
            ['entity' => $orderType],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/attributes/poNumber']
                ],
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/included/0/attributes/quantity']
                ],
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/included/2/attributes/quantity']
                ]
            ],
            $response
        );
    }

    public function testCreateWithDirectOrderOfRelatedEntities()
    {
        $orderType = $this->getEntityType(TestOrder::class);
        $orderLineItemType = $this->getEntityType(TestOrderLineItem::class);
        $productType = $this->getEntityType(TestProduct::class);

        $data = [
            'data'     => [
                'type'          => $orderType,
                'attributes'    => [
                    'poNumber' => 'PO1'
                ],
                'relationships' => [
                    'items' => [
                        'data' => [
                            ['type' => $orderLineItemType, 'id' => 'item1']
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => $productType,
                    'id'         => 'product1',
                    'attributes' => [
                        'name' => 'Product 1'
                    ]
                ],
                [
                    'type'          => $orderLineItemType,
                    'id'            => 'item1',
                    'attributes'    => [
                        'quantity' => 10
                    ],
                    'relationships' => [
                        'product' => [
                            'data' => ['type' => $productType, 'id' => 'product1']
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => $orderType], $data);

        $expectedResponse = $data;
        $expectedResponse['data']['id'] = 'new';
        $expectedResponse['data']['relationships']['items']['data'][0]['id'] = 'new';
        $expectedResponse['included'][0]['id'] = 'new';
        $expectedResponse['included'][0]['meta']['includeId'] = 'product1';
        $expectedResponse['included'][1]['id'] = 'new';
        $expectedResponse['included'][1]['meta']['includeId'] = 'item1';
        $expectedResponse['included'][1]['relationships']['order']['data']['id'] = 'new';
        $expectedResponse['included'][1]['relationships']['product']['data']['id'] = 'new';
        $expectedResponse = $this->updateResponseContent($expectedResponse, $response);
        $this->assertResponseContains($expectedResponse, $response);

        $orderId = (int)$this->getResourceId($response);
        /** @var TestOrder $order */
        $order = $this->getEntityManager()->find(TestOrder::class, $orderId);
        self::assertNotNull($order);
        self::assertEquals('PO1', $order->getPoNumber());
        self::assertCount(1, $order->getLineItems());
        /** @var TestOrderLineItem $orderLineItem */
        $orderLineItem = $order->getLineItems()->first();
        self::assertNotNull($orderLineItem->getOrder());
        self::assertEquals($orderId, $orderLineItem->getOrder()->getId());
        self::assertEquals(10, $orderLineItem->getQuantity());
        self::assertNotNull($orderLineItem->getProduct());
        self::assertEquals('Product 1', $orderLineItem->getProduct()->getName());
    }

    public function testCreateWithInverseOrderOfRelatedEntities()
    {
        $orderType = $this->getEntityType(TestOrder::class);
        $orderLineItemType = $this->getEntityType(TestOrderLineItem::class);
        $productType = $this->getEntityType(TestProduct::class);

        $data = [
            'data'     => [
                'type'       => $orderType,
                'id'         => 'order',
                'attributes' => [
                    'poNumber' => 'PO1'
                ]
            ],
            'included' => [
                [
                    'type'          => $orderLineItemType,
                    'id'            => 'item1',
                    'attributes'    => [
                        'quantity' => 10
                    ],
                    'relationships' => [
                        'order'   => [
                            'data' => ['type' => $orderType, 'id' => 'order']
                        ],
                        'product' => [
                            'data' => ['type' => $productType, 'id' => 'product1']
                        ]
                    ]
                ],
                [
                    'type'       => $productType,
                    'id'         => 'product1',
                    'attributes' => [
                        'name' => 'Product 1'
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => $orderType], $data);

        $expectedResponse = $data;
        $expectedResponse['data']['id'] = 'new';
        $expectedResponse['data']['relationships']['items']['data'][0]['id'] = 'new';
        $expectedResponse['included'][0]['id'] = 'new';
        $expectedResponse['included'][0]['meta']['includeId'] = 'item1';
        $expectedResponse['included'][0]['relationships']['order']['data']['id'] = 'new';
        $expectedResponse['included'][0]['relationships']['product']['data']['id'] = 'new';
        $expectedResponse['included'][1]['id'] = 'new';
        $expectedResponse['included'][1]['meta']['includeId'] = 'product1';
        $expectedResponse = $this->updateResponseContent($expectedResponse, $response);
        $this->assertResponseContains($expectedResponse, $response);

        $orderId = (int)$this->getResourceId($response);
        /** @var TestOrder $order */
        $order = $this->getEntityManager()->find(TestOrder::class, $orderId);
        self::assertNotNull($order);
        self::assertEquals('PO1', $order->getPoNumber());
        self::assertCount(1, $order->getLineItems());
        /** @var TestOrderLineItem $orderLineItem */
        $orderLineItem = $order->getLineItems()->first();
        self::assertNotNull($orderLineItem->getOrder());
        self::assertEquals($orderId, $orderLineItem->getOrder()->getId());
        self::assertEquals(10, $orderLineItem->getQuantity());
        self::assertNotNull($orderLineItem->getProduct());
        self::assertEquals('Product 1', $orderLineItem->getProduct()->getName());
    }

    public function testCreateChildEntityTogetherWithParentEntityWithDirectOrderOfRelatedEntities()
    {
        $orderType = $this->getEntityType(TestOrder::class);
        $orderLineItemType = $this->getEntityType(TestOrderLineItem::class);
        $productType = $this->getEntityType(TestProduct::class);

        $data = [
            'data'     => [
                'type'          => $orderLineItemType,
                'attributes'    => [
                    'quantity' => 10
                ],
                'relationships' => [
                    'order'   => [
                        'data' => ['type' => $orderType, 'id' => 'order1']
                    ],
                    'product' => [
                        'data' => ['type' => $productType, 'id' => 'product1']
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => $productType,
                    'id'         => 'product1',
                    'attributes' => [
                        'name' => 'Product 1'
                    ]
                ],
                [
                    'type'       => $orderType,
                    'id'         => 'order1',
                    'attributes' => [
                        'poNumber' => 'PO1'
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => $orderLineItemType], $data);

        $expectedResponse = $data;
        $expectedResponse['data']['id'] = 'new';
        $expectedResponse['data']['relationships']['order']['data']['id'] = 'new';
        $expectedResponse['data']['relationships']['product']['data']['id'] = 'new';
        $expectedResponse['included'][0]['id'] = 'new';
        $expectedResponse['included'][0]['meta']['includeId'] = 'product1';
        $expectedResponse['included'][1]['id'] = 'new';
        $expectedResponse['included'][1]['meta']['includeId'] = 'order1';
        $expectedResponse['included'][1]['relationships']['items']['data'][0] = [
            'type' => $orderLineItemType,
            'id'   => 'new'
        ];
        $expectedResponse = $this->updateResponseContent($expectedResponse, $response);
        $this->assertResponseContains($expectedResponse, $response);

        $orderId = (int)$expectedResponse['included'][1]['id'];
        /** @var TestOrder $order */
        $order = $this->getEntityManager()->find(TestOrder::class, $orderId);
        self::assertNotNull($order);
        self::assertEquals('PO1', $order->getPoNumber());
        self::assertCount(1, $order->getLineItems());
        /** @var TestOrderLineItem $orderLineItem */
        $orderLineItem = $order->getLineItems()->first();
        self::assertNotNull($orderLineItem->getOrder());
        self::assertEquals($orderId, $orderLineItem->getOrder()->getId());
        self::assertEquals(10, $orderLineItem->getQuantity());
        self::assertNotNull($orderLineItem->getProduct());
        self::assertEquals('Product 1', $orderLineItem->getProduct()->getName());
    }

    public function testCreateChildEntityTogetherWithParentEntityWithInverseOrderOfRelatedEntities()
    {
        $orderType = $this->getEntityType(TestOrder::class);
        $orderLineItemType = $this->getEntityType(TestOrderLineItem::class);
        $productType = $this->getEntityType(TestProduct::class);

        $data = [
            'data'     => [
                'type'          => $orderLineItemType,
                'id'            => 'item',
                'attributes'    => [
                    'quantity' => 10
                ],
                'relationships' => [
                    'product' => [
                        'data' => ['type' => $productType, 'id' => 'product1']
                    ]
                ]
            ],
            'included' => [
                [
                    'type'          => $orderType,
                    'id'            => 'order1',
                    'attributes'    => [
                        'poNumber' => 'PO1'
                    ],
                    'relationships' => [
                        'items' => [
                            'data' => [
                                ['type' => $orderLineItemType, 'id' => 'item']
                            ]
                        ]
                    ]
                ],
                [
                    'type'       => $productType,
                    'id'         => 'product1',
                    'attributes' => [
                        'name' => 'Product 1'
                    ]
                ]
            ]
        ];

        $response = $this->post(['entity' => $orderLineItemType], $data);

        $expectedResponse = $data;
        $expectedResponse['data']['id'] = 'new';
        $expectedResponse['data']['relationships']['order']['data']['id'] = 'new';
        $expectedResponse['data']['relationships']['product']['data']['id'] = 'new';
        $expectedResponse['included'][0]['id'] = 'new';
        $expectedResponse['included'][0]['meta']['includeId'] = 'order1';
        $expectedResponse['included'][0]['relationships']['items']['data'][0]['id'] = 'new';
        $expectedResponse['included'][1]['id'] = 'new';
        $expectedResponse['included'][1]['meta']['includeId'] = 'product1';
        $expectedResponse = $this->updateResponseContent($expectedResponse, $response);
        $this->assertResponseContains($expectedResponse, $response);

        $orderId = (int)$expectedResponse['included'][0]['id'];
        /** @var TestOrder $order */
        $order = $this->getEntityManager()->find(TestOrder::class, $orderId);
        self::assertNotNull($order);
        self::assertEquals('PO1', $order->getPoNumber());
        self::assertCount(1, $order->getLineItems());
        /** @var TestOrderLineItem $orderLineItem */
        $orderLineItem = $order->getLineItems()->first();
        self::assertNotNull($orderLineItem->getOrder());
        self::assertEquals($orderId, $orderLineItem->getOrder()->getId());
        self::assertEquals(10, $orderLineItem->getQuantity());
        self::assertNotNull($orderLineItem->getProduct());
        self::assertEquals('Product 1', $orderLineItem->getProduct()->getName());
    }
}
