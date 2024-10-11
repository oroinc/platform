<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class DisableValidateOperationTest extends RestJsonApiTestCase
{
    public function testTryToCreateWithValidateOperation(): void
    {
        $response = $this->post(
            ['entity' => 'testproducts'],
            [
                'data' => [
                    'type'       => 'testproducts',
                    'meta'       => ['validate' => true],
                    'attributes' => [
                        'name' => 'Test Product 1'
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'value constraint',
                'detail' => 'The option is not supported.',
                'source' => ['pointer' => '/meta/validate']
            ],
            $response
        );
    }

    public function testTryToUpdateWithValidateOperation(): void
    {
        $response = $this->patch(
            ['entity' => 'testproducts', 'id' => '1'],
            [
                'data' => [
                    'type'       => 'testproducts',
                    'id'         => '1',
                    'meta'       => ['validate' => true],
                    'attributes' => [
                        'name' => 'Test Product 1 (updated)'
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'value constraint',
                'detail' => 'The option is not supported.',
                'source' => ['pointer' => '/meta/validate']
            ],
            $response
        );
    }

    public function testTryToCreateWithCoupleMetaOptionsThatEqualsTrue(): void
    {
        $response = $this->post(
            ['entity' => 'testproducts'],
            [
                'data' => [
                    'type'       => 'testproducts',
                    'meta'       => ['validate' => true, 'update' => true],
                    'attributes' => [
                        'name' => 'Test Product 1'
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'request data constraint',
                'detail' => 'Only one meta option can be used.',
                'source' => ['pointer' => '/meta']
            ],
            $response
        );
    }
}
