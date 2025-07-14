<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Test;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\IntegrationBundle\Test\FakeRestClient;
use Oro\Bundle\IntegrationBundle\Test\FakeRestResponse as Response;
use PHPUnit\Framework\TestCase;

/**
 * Class FakeRestClientTest is simple test for fake client just to ensure it's logic works
 */
class FakeRestClientTest extends TestCase
{
    private const FAKE_RESOURCE = '/foo';

    private FakeRestClient $client;

    #[\Override]
    protected function setUp(): void
    {
        $this->client = new FakeRestClient();
    }

    public function testCrudCorrectResponse(): void
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

    public function testGetLastResponse(): void
    {
        $this->assertNull($this->client->getLastResponse());

        $this->client->setDefaultResponse(new Response(200));
        $restResponse = $this->client->get(self::FAKE_RESOURCE);

        $this->assertCorrectRestResponse(200, $restResponse);
        $this->assertSame($restResponse, $this->client->getLastResponse());
    }

    public function testResourceErrorResponse(): void
    {
        $this->expectException(RestException::class);

        $this->client->setDefaultResponse(new Response(404));
        $this->client->get(self::FAKE_RESOURCE);
    }

    public function testGetJsonReturnArray(): void
    {
        $this->client->setDefaultResponse(new Response(200, [], '[]'));

        $this->assertIsArray($this->client->getJSON(self::FAKE_RESOURCE));
    }

    public function testSetResponseList(): void
    {
        $this->client->setResponseList([
            '/foo' => new Response(200),
            '/bar' => new Response(304),
        ]);

        $this->assertCorrectRestResponse(200, $this->client->get(self::FAKE_RESOURCE));
        $this->assertCorrectRestResponse(304, $this->client->get('/bar'));
    }

    /**
     * Asserts that client returned valid RestResponse with expected status code
     */
    private function assertCorrectRestResponse(
        int $expectedStatusCode,
        RestResponseInterface $restResponse,
        string $errorMessage = ''
    ): void {
        $this->assertInstanceOf(RestResponseInterface::class, $restResponse, $errorMessage);
        $this->assertEquals($expectedStatusCode, $restResponse->getStatusCode(), $errorMessage);
    }
}
