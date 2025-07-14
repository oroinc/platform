<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestOrder;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @dbIsolationPerTest
 * @nestTransactionsWithSavepoints
 */
class ValidateEntityTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/test_target.yml',
            '@OroApiBundle/Tests/Functional/DataFixtures/test_order.yml'
        ]);
    }

    public function testTryToGetListWithMetaValidateFilter(): void
    {
        $response = $this->cget(
            ['entity' => 'testapiorders'],
            ['meta' => ['validate' => true]],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'filter constraint',
                'detail' => 'The filter is not supported.',
                'source' => ['parameter' => 'meta[validate]']
            ],
            $response
        );
    }

    public function testTryToGetWithMetaValidateFilter(): void
    {
        $response = $this->get(
            ['entity' => 'testapiorders', 'id' => '@test_order->id'],
            ['meta' => ['validate' => true]],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'filter constraint',
                'detail' => 'The filter is not supported.',
                'source' => ['parameter' => 'meta[validate]']
            ],
            $response
        );
    }

    public function testTryToDeleteListWithMetaValidateFilter(): void
    {
        $response = $this->cdelete(
            ['entity' => 'testapiorders'],
            ['filter' => ['targetEntity' => '@test_target->id'], 'meta' => ['validate' => true]],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'filter constraint',
                'detail' => 'The filter is not supported.',
                'source' => ['parameter' => 'meta[validate]']
            ],
            $response
        );
    }

    public function testCreateWhenValidateFlagEqualsToTrue(): void
    {
        $response = $this->post(
            ['entity' => 'testapiorders'],
            [
                'data' => [
                    'meta' => ['validate' => true],
                    'type' => 'testapiorders',
                    'attributes' => [
                        'poNumber' => 'test order'
                    ]
                ]
            ]
        );
        $content = self::jsonToArray($response->getContent());

        self::assertEquals('test order', $content['data']['attributes']['poNumber']);

        $testOrder = $this->getEntityManager()->find(TestOrder::class, $content['data']['id']);
        self::assertTrue(null === $testOrder);
    }

    public function testCreateWhenValidateFlagEqualsToFalse(): void
    {
        $response = $this->post(
            ['entity' => 'testapiorders'],
            [
                'data' => [
                    'meta' => ['validate' => false],
                    'type' => 'testapiorders',
                    'attributes' => [
                        'poNumber' => 'test order'
                    ]
                ]
            ]
        );
        $content = self::jsonToArray($response->getContent());

        $testOrder = $this->getEntityManager()->find(TestOrder::class, $content['data']['id']);
        self::assertFalse(null === $testOrder);
        self::assertEquals('test order', $testOrder->getPoNumber());
    }

    public function testCreateWithCoupleMetaFlags(): void
    {
        $response = $this->post(
            ['entity' => 'testapiorders'],
            [
                'data' => [
                    'meta' => ['validate' => true, 'upsert' => false],
                    'type' => 'testapiorders',
                    'attributes' => [
                        'poNumber' => 'test order'
                    ]
                ]
            ]
        );
        $content = self::jsonToArray($response->getContent());

        self::assertEquals('test order', $content['data']['attributes']['poNumber']);

        $testOrder = $this->getEntityManager()->find(TestOrder::class, $content['data']['id']);
        self::assertTrue(null === $testOrder);
    }

    public function testUpdateWhenValidateFlagEqualsToTrue(): void
    {
        $testTarget = $this->getReference('test_target');

        $response = $this->patch(
            ['entity' => 'testapiorders', 'id' => '<toString(@test_order->id)>'],
            [
                'data' => [
                    'meta' => ['validate' => true],
                    'id' => '<toString(@test_order->id)>',
                    'type' => 'testapiorders',
                    'attributes' => [
                        'poNumber' => 'test order'
                    ],
                    'relationships' => [
                        'targetEntity' => [
                            'data' => [
                                'type' => 'testapitargets',
                                'id' => '<toString(@test_target->id)>',
                            ]
                        ]

                    ]
                ]
            ]
        );
        $content = self::jsonToArray($response->getContent());

        self::assertEquals('test order', $content['data']['attributes']['poNumber']);
        self::assertEquals($testTarget->getId(), $content['data']['relationships']['targetEntity']['data']['id']);

        $testOrder = $this->getEntityManager()->find(TestOrder::class, $content['data']['id']);
        self::assertTrue(null === $testOrder->getTarget());
        self::assertEquals('TestPONumber', $testOrder->getPoNumber());
    }

    public function testUpdateWhenValidateFlagEqualsToFalse(): void
    {
        $testTarget = $this->getReference('test_target');

        $response = $this->patch(
            ['entity' => 'testapiorders', 'id' => '<toString(@test_order->id)>'],
            [
                'data' => [
                    'meta' => ['validate' => false],
                    'id' => '<toString(@test_order->id)>',
                    'type' => 'testapiorders',
                    'attributes' => [
                        'poNumber' => 'test order'
                    ],
                    'relationships' => [
                        'targetEntity' => [
                            'data' => [
                                'type' => 'testapitargets',
                                'id' => '<toString(@test_target->id)>',
                            ]
                        ]

                    ]
                ]
            ]
        );
        $content = self::jsonToArray($response->getContent());

        self::assertEquals('test order', $content['data']['attributes']['poNumber']);
        self::assertEquals($testTarget->getId(), $content['data']['relationships']['targetEntity']['data']['id']);

        $testOrder = $this->getEntityManager()->find(TestOrder::class, $content['data']['id']);
        self::assertEquals($testTarget->getId(), $testOrder->getTarget()->getId());
        self::assertEquals('test order', $testOrder->getPoNumber());
    }

    public function testUpdateWithCoupleMetaFlags(): void
    {
        $response = $this->patch(
            ['entity' => 'testapiorders', 'id' => '<toString(@test_order->id)>'],
            [
                'data' => [
                    'meta' => ['validate' => true, 'upsert' => false],
                    'type' => 'testapiorders',
                    'id' => '<toString(@test_order->id)>',
                    'attributes' => [
                        'poNumber' => 'test order'
                    ]
                ]
            ]
        );
        $content = self::jsonToArray($response->getContent());

        self::assertEquals('test order', $content['data']['attributes']['poNumber']);

        $testOrder = $this->getEntityManager()->find(TestOrder::class, $content['data']['id']);
        self::assertEquals('TestPONumber', $testOrder->getPoNumber());
    }

    public function testValidateFlagForIncludedEntity(): void
    {
        $response = $this->post(
            ['entity' => 'testapiorders'],
            [
                'data' => [
                    'type' => 'testapiorders',
                    'attributes' => [
                        'poNumber' => 'test order'
                    ],
                    'relationships' => [
                        'lineItems' => [
                            'data' => [
                                ['type' => 'testapiorderlineitems', 'id' => 'line_item_1']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'testapiorderlineitems',
                        'id' => 'line_item_1',
                        'meta' => ['validate' => true],
                        'attributes' => [
                            'quantity' => 1
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'value constraint',
                'detail' => 'The option is not supported.',
                'source' => ['pointer' => '/included/0/meta/validate']
            ],
            $response
        );
    }
}
