<?php

namespace Oro\Bundle\TrackingBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class TrackingDataControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    /**
     * @param array $options
     * @dataProvider optionsProvider
     */
    public function testCreate(array $options)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_tracking_data_create', $options),
            [],
            [],
            $this->generateBasicAuthHeader()
        );
        $response = $this->client->getResponse();
        $result   = $this->getJsonResponseContent($response, 201);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayNotHasKey('errors', $result);
        $this->assertArrayNotHasKey('validation', $result);
    }

    /**
     * @return array
     */
    public function optionsProvider()
    {
        return [
            'simple' => [
                [
                    'param'    => 'value',
                    'url'      => 'example.com',
                    'user'     => 'user',
                    'loggedAt' => '2014-07-18T15:00:00+0300'
                ]
            ],
            'event'  => [
                [
                    'param'    => 'value',
                    'name'     => 'name',
                    'value'    => 'value',
                    'user'     => 'user',
                    'url'      => 'example.com',
                    'loggedAt' => '2014-07-18T15:00:00+0300'
                ]
            ],
        ];
    }

    /**
     * @param array $options
     * @param array $expectedMessages
     * @dataProvider validationProvider
     */
    public function testValidation(array $options, array $expectedMessages)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_tracking_data_create', $options),
            [],
            [],
            $this->generateBasicAuthHeader()
        );
        $response = $this->client->getResponse();
        $result   = $this->getJsonResponseContent($response, 400);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayNotHasKey('errors', $result);
        $errors = implode(', ', $result['validation']);
        foreach ($expectedMessages as $expectedMessage) {
            $this->assertContains($expectedMessage, $errors);
        }
    }

    /**
     * @return array
     */
    public function validationProvider()
    {
        return [
            'empty' => [
                [],
                [
                    'event.user: This value should not be blank',
                    'event.url: This value should not be blank',
                    'event.loggedAt: This value should not be blank',
                ]
            ],
            'user'  => [
                [
                    'user' => 'user'
                ],
                [
                    'event.url: This value should not be blank',
                    'event.loggedAt: This value should not be blank',
                ]
            ],
            'url'   => [
                [
                    'user' => 'user',
                    'url'  => 'example.com'
                ],
                [
                    'event.loggedAt: This value should not be blank',
                ]
            ],
        ];
    }
}
