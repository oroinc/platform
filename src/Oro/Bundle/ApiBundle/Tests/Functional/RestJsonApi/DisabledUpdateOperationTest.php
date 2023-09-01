<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class DisabledUpdateOperationTest extends RestJsonApiTestCase
{
    public function testTryToCreateWithUpdateOperation(): void
    {
        $response = $this->post(
            ['entity' => 'testproducts'],
            [
                'data' => [
                    'type'       => 'testproducts',
                    'meta'       => ['update' => true],
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
                'source' => ['pointer' => '/meta/update']
            ],
            $response
        );
    }

    public function testTryToUpdateWithUpdateOperation(): void
    {
        $response = $this->patch(
            ['entity' => 'testproducts', 'id' => '1'],
            [
                'data' => [
                    'type'       => 'testproducts',
                    'id'         => '1',
                    'meta'       => ['update' => true],
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
                'source' => ['pointer' => '/meta/update']
            ],
            $response
        );
    }
}
