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
    private const FAKE_RESOURCE = '/foo';

    /** @var FakeRestClient */
    private $client;

    #[\Override]
    protected function setUp(): void
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

        $this->assertIsArray($this->client->getJSON(self::FAKE_RESOURCE));
    }

    public function testSetResponseList()
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
        mixed $restResponse,
        string $errorMessage = ''
    ): void {
        $this->assertInstanceOf(RestResponseInterface::class, $restResponse, $errorMessage);
        /** @var RestResponseInterface $restResponse */
        $this->assertEquals($expectedStatusCode, $restResponse->getStatusCode(), $errorMessage);
    }

    public function testGetJsonDoesNotTriggerDeprecation()
    {
        $this->client->setDefaultResponse(new Response(200, [], '{"key":"val"}'));

        $deprecations = [];
        set_error_handler(
            static function (int $errno, string $errstr) use (&$deprecations): bool {
                $deprecations[] = $errstr;

                return true;
            },
            E_USER_DEPRECATED
        );

        try {
            $data = $this->client->getJSON(self::FAKE_RESOURCE);
        } finally {
            restore_error_handler();
        }

        self::assertSame(['key' => 'val'], $data);
        self::assertSame([], $deprecations);
    }

    public function testGetJsonThrowsExceptionWhenBodyIsNotValidJson()
    {
        $this->client->setDefaultResponse(new Response(200, [], 'not a json'));

        $this->expectException(RestException::class);
        $this->expectExceptionMessage('Unable to parse response body into JSON');

        $this->client->getJSON(self::FAKE_RESOURCE);
    }
}
