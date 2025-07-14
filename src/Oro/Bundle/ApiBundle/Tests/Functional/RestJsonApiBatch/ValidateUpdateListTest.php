<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiBatch;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestOrder;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiUpdateListTestCase;

/**
 * @dbIsolationPerTest
 * @nestTransactionsWithSavepoints
 */
class ValidateUpdateListTest extends RestJsonApiUpdateListTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroApiBundle/Tests/Functional/DataFixtures/test_order.yml'
        ]);
    }

    public function testUpdateListWhenValidateEqualsToTrue(): void
    {
        $this->processUpdateList(
            TestOrder::class,
            [
                'data' => [
                    [
                        'meta'       => ['validate' => true, 'upsert' => false],
                        'id'         => '<toString(@test_order->id)>',
                        'type'       => 'testapiorders',
                        'attributes' => ['poNumber' => 'test order']
                    ]
                ]
            ]
        );

        $this->getEntityManager()->clear();

        $testOrders = $this->getEntityManager()->getRepository(TestOrder::class)->findAll();

        $response = $this->cget(['entity' => 'testapiorders']);
        $content = $this->updateResponseContent(
            [
                'data' => [
                    [
                        'id'         => '<toString(@test_order->id)>',
                        'attributes' => ['poNumber' => 'TestPONumber']
                    ]
                ]
            ],
            $response
        );

        self::assertCount(2, $testOrders);
        $this->assertResponseContains($content, $response);
    }

    public function testTryToUpdateListWithValidateAndUpdateFlags(): void
    {
        $operationId = $this->processUpdateList(
            TestOrder::class,
            [
                'data' => [
                    [
                        'meta'       => ['validate' => true, 'update' => true],
                        'id'         => '<toString(@test_order->id)>',
                        'type'       => 'testapiorders',
                        'attributes' => ['poNumber' => 'test order']
                    ]
                ]
            ],
            false
        );

        $expectedErrors = [
            [
                'id'     => $operationId . '-1-1',
                'status' => 400,
                'title'  => 'request data constraint',
                'detail' => 'Only one meta option can be used.',
                'source' => ['pointer' => '/data/0/meta']
            ],
        ];
        $this->assertAsyncOperationErrors($expectedErrors, $operationId);
    }
}
