<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ApiDocControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testUnknownView()
    {
        $this->client->request(
            'GET',
            $this->getUrl('nelmio_api_doc_index', ['view' => 'unknown'])
        );
        self::assertResponseStatusCodeEquals($this->client->getResponse(), 404);
    }

    public function testDefaultView()
    {
        $this->client->request(
            'GET',
            $this->getUrl('nelmio_api_doc_index')
        );
        self::assertResponseStatusCodeEquals($this->client->getResponse(), 200);
    }

    public function testRestJsonApiView()
    {
        $this->client->request(
            'GET',
            $this->getUrl('nelmio_api_doc_index', ['view' => 'rest_json_api'])
        );
        self::assertResponseStatusCodeEquals($this->client->getResponse(), 200);
    }

    public function testRestPlainView()
    {
        $this->client->request(
            'GET',
            $this->getUrl('nelmio_api_doc_index', ['view' => 'rest_plain'])
        );
        self::assertResponseStatusCodeEquals($this->client->getResponse(), 200);
    }
}
