<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Model\TestResourceWithoutIdentifier;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @dbIsolationPerTest
 */
class FieldDirectionTest extends RestJsonApiTestCase
{
    public function testModelWithoutDirection()
    {
        $response = $this->post(
            ['entity' => 'testapiresourcewithoutidentifier'],
            [
                'meta' => [
                    'name'        => 'A name',
                    'description' => 'A description'
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'name'        => 'A name',
                    'description' => 'A description'
                ]
            ],
            $response
        );
    }

    public function testInputOnlyFieldShouldNotBeInResponseData()
    {
        $this->appendEntityConfig(
            TestResourceWithoutIdentifier::class,
            [
                'actions' => [
                    'create' => [
                        'fields' => [
                            'description' => [
                                'direction' => 'input-only'
                            ]
                        ]
                    ]
                ]
            ]
        );

        $response = $this->post(
            ['entity' => 'testapiresourcewithoutidentifier'],
            [
                'meta' => [
                    'name'        => 'A name',
                    'description' => 'A description'
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'name' => 'A name'
                ]
            ],
            $response
        );
    }

    public function testOutputOnlyFieldShouldBeInResponseData()
    {
        $this->appendEntityConfig(
            TestResourceWithoutIdentifier::class,
            [
                'actions' => [
                    'create' => [
                        'fields' => [
                            'description' => [
                                'direction' => 'output-only'
                            ]
                        ]
                    ]
                ]
            ]
        );

        $response = $this->post(
            ['entity' => 'testapiresourcewithoutidentifier'],
            [
                'meta' => [
                    'name' => 'A name'
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'meta' => [
                    'name'        => 'A name',
                    'description' => null
                ]
            ],
            $response
        );
    }

    public function testOutputOnlyFieldShouldNotBeAcceptedInRequestData()
    {
        $this->appendEntityConfig(
            TestResourceWithoutIdentifier::class,
            [
                'actions' => [
                    'create' => [
                        'fields' => [
                            'description' => [
                                'direction' => 'output-only'
                            ]
                        ]
                    ]
                ]
            ]
        );

        $response = $this->post(
            ['entity' => 'testapiresourcewithoutidentifier'],
            [
                'meta' => [
                    'name'        => 'A name',
                    'description' => null
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'extra fields constraint',
                'detail' => 'This form should not contain extra fields: "description".'
            ],
            $response
        );
    }
}
