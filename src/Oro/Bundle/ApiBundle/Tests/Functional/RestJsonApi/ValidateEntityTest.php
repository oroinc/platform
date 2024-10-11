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
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/test_target.yml',
            '@OroApiBundle/Tests/Functional/DataFixtures/test_order.yml'
        ]);
    }

    public function testTryToGetListWithValidateFlag(): void
    {
        $response = $this->cget(['entity' => 'testapiorders'], ['meta' => ['validate' => true]], [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The filter is not supported.',
                'source' => ['parameter' => 'meta[validate]']
            ],
            $response
        );
    }

    public function testTryToGetWithValidateFlag(): void
    {
        $response = $this->get(
            ['entity' => 'testapiorders', 'id' => '@test_order->id'],
            ['meta' => ['validate' => true]],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The filter is not supported.',
                'source' => ['parameter' => 'meta[validate]']
            ],
            $response
        );
    }

    public function testTryToDeleteListWithValidateFlag(): void
    {
        $response = $this->cdelete(
            ['entity' => 'testapiorders'],
            ['filter' => ['targetEntity' => '@test_target->id'], 'meta' => ['validate' => true]],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The filter is not supported.',
                'source' => ['parameter' => 'meta[validate]']
            ],
            $response
        );
    }

    public function testCreateWhenValidateEqualsToTrue(): void
    {
        $poNumber = 'test order';

        $data = [
            'data' => [
                'meta'       => ['validate' => true],
                'type'       => 'testapiorders',
                'attributes' => [
                    'poNumber' => $poNumber
                ]
            ]
        ];

        $response = $this->post(['entity' => 'testapiorders'], $data);
        $content = self::jsonToArray($response->getContent());

        self::assertEquals($poNumber, $content['data']['attributes']['poNumber']);

        $this->getEntityManager()->clear();
        $testOrder = $this->getEntityManager()->find(TestOrder::class, $content['data']['id']);
        self::assertNull($testOrder);
    }

    public function testCreateWhenValidateEqualsToFalse(): void
    {
        $poNumber = 'test order';

        $data = [
            'data' => [
                'meta'       => ['validate' => false],
                'type'       => 'testapiorders',
                'attributes' => [
                    'poNumber' => $poNumber
                ]
            ]
        ];

        $response = $this->post(['entity' => 'testapiorders'], $data);
        $content = self::jsonToArray($response->getContent());

        $this->getEntityManager()->clear();
        $testOrder = $this->getEntityManager()->find(TestOrder::class, $content['data']['id']);

        self::assertNotNull($testOrder);
        self::assertEquals($poNumber, $testOrder->getPoNumber());
    }

    public function testCreateWithCoupleMetaOptions(): void
    {
        $poNumber = 'test order';

        $data = [
            'data' => [
                'meta'       => ['validate' => true, 'upsert' => false],
                'type'       => 'testapiorders',
                'attributes' => [
                    'poNumber' => $poNumber
                ]
            ]
        ];

        $response = $this->post(['entity' => 'testapiorders'], $data);
        $content = self::jsonToArray($response->getContent());

        self::assertEquals($poNumber, $content['data']['attributes']['poNumber']);

        $this->getEntityManager()->clear();
        $testOrder = $this->getEntityManager()->find(TestOrder::class, $content['data']['id']);

        self::assertNull($testOrder);
    }

    public function testUpdateWhenValidateEqualsToTrue(): void
    {
        $testTarget = $this->getReference('test_target');

        $data = [
            'data' => [
                'meta'       => ['validate' => true],
                'id'         => '<toString(@test_order->id)>',
                'type'       => 'testapiorders',
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
        ];

        $response = $this->patch(['entity' => 'testapiorders', 'id' => '<toString(@test_order->id)>'], $data);
        $content = self::jsonToArray($response->getContent());

        self::assertEquals('test order', $content['data']['attributes']['poNumber']);
        self::assertEquals($testTarget->getId(), $content['data']['relationships']['targetEntity']['data']['id']);

        $this->getEntityManager()->clear();
        $testOrder = $this->getEntityManager()->find(TestOrder::class, $content['data']['id']);

        self::assertNull($testOrder->getTarget());
        self::assertEquals('TestPONumber', $testOrder->getPoNumber());
    }

    public function testUpdateWhenValidateEqualsToFalse(): void
    {
        $testTarget = $this->getReference('test_target');

        $data = [
            'data' => [
                'meta'       => ['validate' => false],
                'id'         => '<toString(@test_order->id)>',
                'type'       => 'testapiorders',
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
        ];

        $response = $this->patch(['entity' => 'testapiorders', 'id' => '<toString(@test_order->id)>'], $data);
        $content = self::jsonToArray($response->getContent());

        self::assertEquals('test order', $content['data']['attributes']['poNumber']);
        self::assertEquals($testTarget->getId(), $content['data']['relationships']['targetEntity']['data']['id']);

        $this->getEntityManager()->clear();
        $testOrder = $this->getEntityManager()->find(TestOrder::class, $content['data']['id']);

        self::assertEquals($testTarget->getId(), $testOrder->getTarget()->getId());
        self::assertEquals('test order', $testOrder->getPoNumber());
    }

    public function testUpdateWithCoupleMetaOptions(): void
    {
        $data = [
            'data' => [
                'meta'       => ['validate' => true, 'upsert' => false],
                'type'       => 'testapiorders',
                'id'         => '<toString(@test_order->id)>',
                'attributes' => [
                    'poNumber' => 'test order'
                ]
            ]
        ];

        $response = $this->patch(['entity' => 'testapiorders', 'id' => '<toString(@test_order->id)>'], $data);
        $content = self::jsonToArray($response->getContent());

        self::assertEquals('test order', $content['data']['attributes']['poNumber']);

        $this->getEntityManager()->clear();
        $testOrder = $this->getEntityManager()->find(TestOrder::class, $content['data']['id']);

        self::assertEquals('TestPONumber', $testOrder->getPoNumber());
    }
}
