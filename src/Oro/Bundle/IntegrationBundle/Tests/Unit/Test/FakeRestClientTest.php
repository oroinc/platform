<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Test;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\IntegrationBundle\Test\FakeRestClient;
use Oro\Bundle\IntegrationBundle\Test\FakeRestResponse as Response;

/**
 * Class FakeRestClientTest is simple test for fake client just to ensure it's logic works
 */
class FakeRestClientTest extends \PHPUnit\Framework\TestCase
{
    const FAKE_RESOURCE = '/foo';

    /** @var FakeRestClient */
    protected $client;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->client = new FakeRestClient();
    }

    public function testCrudCorrectResponse()
    {
        $this->client->setDefaultResponse(new Response(200));
        $this->assertCorrectRestResponse(200, $this->client->get(self::FAKE_RESOURCE), 'GET was failed');

        $this->client->setDefaultResponse(new Response(201));
        $this->assertCorrectRestResponse(201, $this->client->post(self::FAKE_RESOURCE, []), 'POST was failed');

        $this->client->setDefaultResponse(new Response(204));
        $this->assertCorrectRestResponse(204, $this->client->put(self::FAKE_RESOURCE, []), 'PUT was failed');

        $this->client->setDefaultResponse(new Response(204));
        $this->assertCorrectRestResponse(204, $this->client->delete(self::FAKE_RESOURCE), 'DELETE was failed');
    }

    public function testGetLastResponse()
    {
        $this->assertNull($this->client->getLastResponse());

        $this->client->setDefaultResponse(new Response(200));
        $restResponse = $this->client->get(self::FAKE_RESOURCE);

        $this->assertCorrectRestResponse(200, $restResponse);
        $this->assertSame($restResponse, $this->client->getLastResponse());
    }

    public function testResourceErrorResponse()
    {
        $this->expectException(RestException::class);

        $this->client->setDefaultResponse(new Response(404));
        $this->client->get(self::FAKE_RESOURCE);
    }

    public function testGetJsonReturnArray()
    {
        $this->client->setDefaultResponse(new Response(200, [], '[]'));

        $this->assertTrue(is_array($this->client->getJSON(self::FAKE_RESOURCE)));
    }

    public function testSetResponseList()
    {
        $this->client->setResponseList([
            '/foo' => new Response(200),
            '/bar' => new Response(302),
        ]);

        $this->assertCorrectRestResponse(200, $this->client->get(self::FAKE_RESOURCE));
        $this->assertCorrectRestResponse(302, $this->client->get('/bar'));
    }

    /**
     * Asserts that client returned valid RestResponse with expected status code
     *
     * @param int $expectedStatusCode expected status code
     * @param mixed $restResponse actual response from client
     * @param string $errorMessage message which will be shown in case of failed assertion
     */
    private function assertCorrectRestResponse($expectedStatusCode, $restResponse, $errorMessage = null)
    {
        $this->assertInstanceOf(RestResponseInterface::class, $restResponse, $errorMessage);
        /** @var RestResponseInterface $restResponse */
        $this->assertEquals($expectedStatusCode, $restResponse->getStatusCode(), $errorMessage);
    }
}
