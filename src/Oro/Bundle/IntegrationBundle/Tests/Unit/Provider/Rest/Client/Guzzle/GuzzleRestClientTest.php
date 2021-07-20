<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider\Rest\Client\Guzzle;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestClient;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestException;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class GuzzleRestClientTest extends TestCase
{
    /** @var Client|MockObject */
    protected $sourceClient;

    /** @var GuzzleRestClient */
    protected $client;

    /** @var string */
    protected $baseUrl = 'https://example.com/api/';

    /** @var array */
    protected $defaultOptions = ['default' => 'value'];

    protected function setUp(): void
    {
        $this->sourceClient = $this->getMockBuilder(Client::class)->disableOriginalConstructor()->getMock();

        $this->client = new class($this->baseUrl, $this->defaultOptions) extends GuzzleRestClient {
            public function xgetBaseUrl(): string
            {
                return $this->baseUrl;
            }

            public function xgetDefaultOptions(): array
            {
                return $this->defaultOptions;
            }
        };
        $this->client->setGuzzleClient($this->sourceClient);
    }

    public function testConstructor()
    {
        static::assertEquals($this->baseUrl, $this->client->xgetBaseUrl());
        static::assertEquals($this->defaultOptions, $this->client->xgetDefaultOptions());
    }

    public function testGetLastResponseWorks()
    {
        $response = $this->client->get('users');

        static::assertEquals($response, $this->client->getLastResponse());
    }

    /**
     * @dataProvider performRequestDataProvider
     */
    public function testPerformRequestWorks(string $method, array $args, array $expected)
    {
        $response = $this->createMock(Response::class);

        $this->sourceClient->expects(static::once())
            ->method('send')
            ->with(
                $this->callback(
                    function (Request $request) use ($expected) {
                        $this->assertEquals($expected['method'], strtolower($request->getMethod()));
                        $this->assertEquals($expected['url'], $request->getUri());
                        $this->assertEquals(
                            $expected['headers'],
                            array_diff_key($request->getHeaders(), ['Host' => null])
                        );
                        $this->assertEquals($expected['data'], (string) $request->getBody());

                        return true;
                    }
                ),
                $expected['options']
            )
            ->willReturn($response);

        $actual = call_user_func_array([$this->client, $method], $args);

        static::assertInstanceOf(GuzzleRestResponse::class, $actual);
        static::assertEquals($response, $actual->getSourceResponse());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function performRequestDataProvider()
    {
        $date = new \DateTime('2021-01-10T12:26:04+05:00');

        return [
            'get simple' => [
                'method' => 'get',
                'args' => ['resource' => 'https://google.com/api'],
                'expected' => [
                    'method' => 'get',
                    'url' => 'https://google.com/api',
                    'headers' => [],
                    'data' => '',
                    'options' => ['default' => 'value'],
                ]
            ],
            'get with base url' => [
                'method' => 'get',
                'args' => ['resource' => 'users'],
                'expected' => [
                    'method' => 'get',
                    'url' => 'https://example.com/api/users',
                    'headers' => [],
                    'data' => '',
                    'options' => ['default' => 'value'],
                ]
            ],
            'get with params' => [
                'method' => 'get',
                'args' => [
                    'resource' => 'https://google.com/api?v=2',
                    'params' => ['foo' => 'bar', 'date' => $date->format(\DateTime::ATOM)]
                ],
                'expected' => [
                    'method' => 'get',
                    'url' => 'https://google.com/api?v=2&foo=bar&date=2021-01-10T12%3A26%3A04%2B05%3A00',
                    'headers' => [],
                    'data' => '',
                    'options' => ['default' => 'value'],
                ]
            ],
            'get with headers' => [
                'method' => 'get',
                'args' => [
                    'resource' => 'https://google.com/api', 'params' => [], 'headers' => ['Accept' => '*/*'],
                ],
                'expected' => [
                    'method' => 'get',
                    'url' => 'https://google.com/api',
                    'headers' => ['Accept' => ['*/*']],
                    'data' => '',
                    'options' => ['default' => 'value'],
                ]
            ],
            'get with options' => [
                'method' => 'get',
                'args' => [
                    'resource' => 'https://google.com/api', 'params' => [], 'headers' => [],
                    'options' => ['auth' => ['username', 'password']],
                ],
                'expected' => [
                    'method' => 'get',
                    'url' => 'https://google.com/api',
                    'headers' => [],
                    'data' => '',
                    'options' => ['default' => 'value', 'auth' => ['username', 'password']]
                ]
            ],
            'post' => [
                'method' => 'post',
                'args' => [
                    'resource' => 'user', 'data' => 'foo_data', 'headers' => [], 'options' => [],
                ],
                'expected' => [
                    'method' => 'post',
                    'url' => 'https://example.com/api/user',
                    'headers' => [],
                    'data' => 'foo_data',
                    'options' => ['default' => 'value']
                ]
            ],
            'post guess json' => [
                'method' => 'post',
                'args' => [
                    'resource' => 'user', 'data' => ['foo' => 'data'], 'headers' => [], 'options' => [],
                ],
                'expected' => [
                    'method' => 'post',
                    'url' => 'https://example.com/api/user',
                    'headers' => ['Content-Type' => ['application/json']],
                    'data' => '{"foo":"data"}',
                    'options' => ['default' => 'value']
                ]
            ],
            'put force content type' => [
                'method' => 'put',
                'args' => [
                    'resource' => 'user/1',
                    'data' => '{"foo":"data"}',
                    'headers' => ['Content-Type' => 'text/html'],
                    'options' => [],
                ],
                'expected' => [
                    'method' => 'put',
                    'url' => 'https://example.com/api/user/1',
                    'headers' => ['Content-Type' => ['text/html']],
                    'data' => '{"foo":"data"}',
                    'options' => ['default' => 'value'],
                ]
            ],
            'delete' => [
                'method' => 'delete',
                'args' => ['resource' => 'user/1'],
                'expected' => [
                    'method' => 'delete',
                    'url' => 'https://example.com/api/user/1',
                    'headers' => [],
                    'data' => '',
                    'options' => ['default' => 'value']
                ]
            ],
        ];
    }

    public function testPerformRequestThrowException()
    {
        $this->expectException(GuzzleRestException::class);
        $this->expectExceptionMessage('Exception message');

        $method = 'get';
        $url = 'https://google.com/api/v2';

        $this->sourceClient->expects(static::once())->method('send')->willThrowException(
            new \Exception('Exception message')
        );

        $this->client->performRequest($method, $url);
    }

    public function testGetFormattedResult()
    {
        $url = 'https://example.com/api/v2/users.json';
        $params = ['foo' => 'param'];
        $headers = ['foo' => 'header'];
        $options = ['foo' => 'option'];
        $expectedResult = ['foo' => 'data'];

        $response = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();

        $this->sourceClient->expects(static::once())->method('send')->willReturn($response);

        $response->expects(static::any())->method('getStatusCode')->willReturn(200);
        $response->expects(static::once())->method('getBody')->willReturn('{"foo":"data"}');

        static::assertEquals($expectedResult, $this->client->getJSON($url, $params, $headers, $options));
    }

    public function testGetFormattedResultThrowException()
    {
        $url = 'https://example.com/api/v2/users.json';
        $params = ['foo' => 'param'];
        $headers = ['foo' => 'header'];
        $options = ['foo' => 'option'];
        $statusCode = 403;
        $reasonPhrase = 'Forbidden';

        $response = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();

        $this->sourceClient->expects(static::once())->method('send')->willReturn($response);

        $response->expects(static::atLeastOnce())->method('getStatusCode')->willReturn($statusCode);
        $response->expects(static::atLeastOnce())->method('getReasonPhrase')->willReturn($reasonPhrase);
        $body = $this->createMock(StreamInterface::class);
        $body->expects($this->atLeastOnce())
            ->method('isSeekable')
            ->willReturn(false);
        $response->expects(static::atLeastOnce())->method('getBody')->willReturn($body);

        $this->expectException(GuzzleRestException::class);
        $this->expectExceptionMessage(
            "Client error response".PHP_EOL.
            "[status code] $statusCode".PHP_EOL.
            "[reason phrase] $reasonPhrase".PHP_EOL.
            "[url] $url"
        );

        $this->client->getJSON($url, $params, $headers, $options);
    }
}
