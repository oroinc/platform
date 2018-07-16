<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Test;

use Oro\Bundle\IntegrationBundle\Test\FakeRestResponse;

class FakeRestResponseTest extends \PHPUnit\Framework\TestCase
{
    public function testResponseGetStatusCode()
    {
        $response = new FakeRestResponse(200);
        $this->assertEquals(200, $response->getStatusCode());

        $response = new FakeRestResponse(404);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testResponseIsSuccessful()
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

    public function testResponseIsError()
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

    public function testResponseJson()
    {
        $response = new FakeRestResponse(200, ['Content-Type' => 'application/json'], '{"foo": "bar"}');
        $jsonResponse = $response->json();

        $this->assertEquals('bar', $jsonResponse['foo']);
    }

    public function testRequestGetBody()
    {
        $response = new FakeRestResponse(200, [], 'foo');

        $this->assertEquals('foo', $response->getBodyAsString());
        $this->assertEquals('foo', $response->getMessage());
        $this->assertEquals('foo', (string)$response);
    }

    public function testResponseGetHeaders()
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

    public function testResponseTypes()
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
