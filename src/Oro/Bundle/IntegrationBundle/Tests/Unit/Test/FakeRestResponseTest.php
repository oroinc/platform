<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Test;

use Oro\Bundle\IntegrationBundle\Test\FakeRestResponse;
use PHPUnit\Framework\TestCase;

class FakeRestResponseTest extends TestCase
{
    public function testResponseGetStatusCode(): void
    {
        $response = new FakeRestResponse(200);
        $this->assertEquals(200, $response->getStatusCode());

        $response = new FakeRestResponse(404);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testResponseIsSuccessful(): void
    {
        $response = new FakeRestResponse(200);
        $this->assertTrue($response->isSuccessful());

        $response = new FakeRestResponse(302);
        $this->assertTrue($response->isSuccessful());

        $response = new FakeRestResponse(404);
        $this->assertFalse($response->isSuccessful());

        $response = new FakeRestResponse(502);
        $this->assertFalse($response->isSuccessful());
    }

    public function testResponseIsError(): void
    {
        $response = new FakeRestResponse(200);
        $this->assertFalse($response->isError());

        $response = new FakeRestResponse(302);
        $this->assertFalse($response->isError());

        $response = new FakeRestResponse(404);
        $this->assertTrue($response->isError());

        $response = new FakeRestResponse(502);
        $this->assertTrue($response->isError());
    }

    public function testResponseJson(): void
    {
        $response = new FakeRestResponse(200, ['Content-Type' => 'application/json'], '{"foo": "bar"}');
        $jsonResponse = $response->json();

        $this->assertEquals('bar', $jsonResponse['foo']);
    }

    public function testRequestGetBody(): void
    {
        $response = new FakeRestResponse(200, [], 'foo');

        $this->assertEquals('foo', $response->getBodyAsString());
    }

    public function testResponseGetHeaders(): void
    {
        $expectedHeaders = [
            'Content-Type' => 'application/json',
            'foo' => null
        ];
        $response = new FakeRestResponse(200, $expectedHeaders);

        $this->assertEquals($expectedHeaders, $response->getHeaders());
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));
        $this->assertTrue($response->hasHeader('foo'));
        $this->assertFalse($response->hasHeader('baz'));
    }

    public function testResponseTypes(): void
    {
        $response = new FakeRestResponse(100);
        $this->assertTrue($response->isInformational());

        $response = new FakeRestResponse(301);
        $this->assertTrue($response->isRedirect());

        $response = new FakeRestResponse(400);
        $this->assertTrue($response->isClientError());

        $response = new FakeRestResponse(500);
        $this->assertTrue($response->isServerError());
    }
}
