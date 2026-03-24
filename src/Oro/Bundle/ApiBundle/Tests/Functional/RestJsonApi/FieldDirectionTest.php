<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Model\TestResourceWithoutIdentifier;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

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
            ],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_CREATED);
        self::assertResponseContentTypeEquals($response, $this->getResponseContentType());
        self::assertFalse($response->headers->has('Location'), 'The "Location" header must not be returned.');
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
            ],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_CREATED);
        self::assertResponseContentTypeEquals($response, $this->getResponseContentType());
        self::assertFalse($response->headers->has('Location'), 'The "Location" header must not be returned.');
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
            ],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_CREATED);
        self::assertResponseContentTypeEquals($response, $this->getResponseContentType());
        self::assertFalse($response->headers->has('Location'), 'The "Location" header must not be returned.');
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
