<?php

namespace Oro\Bundle\TrackingBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class TrackingDataControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
    }

    public function testCreate()
    {
        $options = [
            'param1' => 'value1',
            'param2' => 'value2',
        ];

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_create_tracking_data', $options),
            [],
            [],
            $this->generateWsseAuthHeader()
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);
    }
}
