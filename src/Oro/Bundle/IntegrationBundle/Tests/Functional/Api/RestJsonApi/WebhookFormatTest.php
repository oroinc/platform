<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class WebhookFormatTest extends RestJsonApiTestCase
{
    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'webhookformats']);

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'webhookformats',
                        'id' => 'default',
                        'attributes' => [
                            'label' => 'Default (JSON:API)'
                        ]
                    ],
                    [
                        'type' => 'webhookformats',
                        'id' => 'thin',
                        'attributes' => [
                            'label' => 'Thin payload'
                        ]
                    ]
                ]
            ],
            $response,
            true
        );
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'webhookformats', 'id' => 'default']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'webhookformats',
                    'id' => 'default',
                    'attributes' => [
                        'label' => 'Default (JSON:API)'
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToGetForUnknownFormat(): void
    {
        $response = $this->get(
            ['entity' => 'webhookformats', 'id' => 'other'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'webhookformats'],
            [
                'data' => [
                    'type' => 'webhookformats',
                    'id' => 'new_format'
                ]
            ],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            ['entity' => 'webhookformats', 'id' => 'default'],
            [
                'data' => [
                    'type' => 'webhookformats',
                    'id' => 'default'
                ]
            ],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'webhookformats', 'id' => 'default'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'webhookformats'],
            ['filter' => ['id' => 'default']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetOptionsForList(): void
    {
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => 'webhookformats']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testOptionsForItem(): void
    {
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => 'webhookformats', 'id' => 'default']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }
}
