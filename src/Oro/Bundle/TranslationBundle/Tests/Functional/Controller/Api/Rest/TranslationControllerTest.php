<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class TranslationControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
    }

    public function testGetListWithTotalCount()
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_get_translations',
                ['domain' => 'validators']
            ),
            [],
            [],
            ['HTTP_X-Include' => 'totalCount']
        );

        $response = $this->client->getResponse();
        $result = $this->getJsonResponseContent($response, 200);
        $this->assertNotEmpty($result);

        $this->assertTrue(
            $response->headers->has('X-Include-Total-Count'),
            'Response headers should have X-Include-Total-Count'
        );
        $this->assertGreaterThan(0, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetListWithoutTotalCount()
    {
        $this->client->request('GET', $this->getUrl('oro_api_get_translations', ['domain' => 'validators']));

        $response = $this->client->getResponse();
        $result = $this->getJsonResponseContent($response, 200);
        $this->assertNotEmpty($result);

        $this->assertFalse(
            $response->headers->has('X-Include-Total-Count'),
            'Response headers should not have X-Include-Total-Count'
        );
    }
}
