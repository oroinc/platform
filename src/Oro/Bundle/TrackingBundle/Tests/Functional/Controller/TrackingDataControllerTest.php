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
    }

    /**
     * @return array
     */
    public function optionsProvider()
    {
        return [
            'simple' => [
                [
                    'param' => 'value',
                ]
            ],
            'event'  => [
                [
                    'param' => 'value',
                    'name'   => 'name',
                    'value'  => 'value',
                    'user'   => 'user',
                ]
            ],
        ];
    }
}
