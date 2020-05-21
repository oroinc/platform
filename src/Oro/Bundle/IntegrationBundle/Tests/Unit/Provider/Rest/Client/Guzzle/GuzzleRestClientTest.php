<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider\Rest\Client\Guzzle;

use Guzzle\Http\Client;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestClient;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestException;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestResponse;
use PHPUnit\Framework\MockObject\MockObject;

class GuzzleRestClientTest extends \PHPUnit\Framework\TestCase
{
    /** @var Client|MockObject */
    protected $sourceClient;

    /** @var GuzzleRestClient */
    protected $client;

    /** @var string */
    protected $baseUrl = 'https://example.com/api';

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
        $request = $this->createMock(RequestInterface::class);
        $this->sourceClient->expects(static::once())->method('createRequest')->willReturn($request);

        $response = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();
        $request->expects(static::once())->method('send')->willReturn($response);

        $response = $this->client->get('users');

        static::assertEquals($response, $this->client->getLastResponse());
    }

    /**
     * @dataProvider performRequestDataProvider
     */
    public function testPerformRequestWorks($method, $args, $expected)
    {
        $request = $this->createMock(RequestInterface::class);
        $this->sourceClient->expects(static::once())
            ->method('createRequest')
            ->with($expected['method'], $expected['url'], $expected['headers'], $expected['data'], $expected['options'])
            ->willReturn($request);

        $response = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();
        $request->expects(static::once())->method('send')->willReturn($response);

        $actual = call_user_func_array([$this->client, $method], $args);

        static::assertInstanceOf(GuzzleRestResponse::class, $actual);
        static::assertEquals($response, $actual->getSourceResponse());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function performRequestDataProvider()
    {
        return [
            'get simple' => [
                'method' => 'get',
                'args' => ['resource' => 'https://google.com/api'],
                'expected' => [
                    'method' => 'get',
                    'url' => 'https://google.com/api',
                    'headers' => [],
                    'data' => null,
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
                    'data' => null,
                    'options' => ['default' => 'value'],
                ]
            ],
            'get with params' => [
                'method' => 'get',
                'args' => ['resource' => 'https://google.com/api?v=2', 'params' => ['foo' => 'bar']],
                'expected' => [
                    'method' => 'get',
                    'url' => 'https://google.com/api?v=2&foo=bar',
                    'headers' => [],
                    'data' => null,
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
                    'headers' => ['Accept' => '*/*'], 'data' => null,
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
                    'data' => null,
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
                    'headers' => ['Content-Type' => 'application/json'],
                    'data' => '{"foo":"data"}',
                    'options' => ['default' => 'value']
                ]
            ],
            'put force content type' => [
                'method' => 'put',
                'args' => [
                    'resource' => 'user/1', 'data' => ['foo' => 'data'],
                    'headers' => ['Content-Type' => 'text/html'], 'options' => [],
                ],
                'expected' => [
                    'method' => 'put',
                    'url' => 'https://example.com/api/user/1',
                    'headers' => ['Content-Type' => 'text/html'],
                    'data' => ['foo' => 'data'],
                    'options' => ['default' => 'value']
                ]
            ],
            'delete' => [
                'method' => 'delete',
                'args' => ['resource' => 'user/1'],
                'expected' => [
                    'method' => 'delete',
                    'url' => 'https://example.com/api/user/1',
                    'headers' => [],
                    'data' => null,
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

        $request = $this->createMock(RequestInterface::class);
        $this->sourceClient->expects(static::once())->method('createRequest')->willReturn($request);

        $request->expects(static::once())->method('send')->willThrowException(new \Exception('Exception message'));

        $this->client->performRequest($method, $url);
    }

    /**
     * @dataProvider getFormattedResultDataProvider
     */
    public function testGetFormattedResult($format)
    {
        $url = 'https://example.com/api/v2/users.' . $format;
        $params = ['foo' => 'param'];
        $headers = ['foo' => 'header'];
        $options = ['foo' => 'option'];
        $expectedResult = ['foo' => 'data'];
        $expectedUrl = $url . '?foo=param';
        $expectedOptions = ['foo' => 'option', 'default' => 'value'];

        $response = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();

        $request = $this->createMock(RequestInterface::class);

        $this->sourceClient->expects(static::once())
            ->method('createRequest')
            ->with('get', $expectedUrl, $headers, null, $expectedOptions)
            ->willReturn($request);

        $request->expects(static::once())->method('send')->willReturn($response);

        $response->expects(static::once())->method('isSuccessful')->willReturn(true);
        $response->expects(static::once())->method($format)->willReturn($expectedResult);

        $getter = 'get' . \strtoupper($format);

        static::assertEquals($expectedResult, $this->client->$getter($url, $params, $headers, $options));
    }

    /**
     * @dataProvider getFormattedResultDataProvider
     */
    public function testGetFormattedResultThrowException($format)
    {
        $url = 'https://example.com/api/v2/users.' . $format;
        $params = ['foo' => 'param'];
        $headers = ['foo' => 'header'];
        $options = ['foo' => 'option'];
        $expectedUrl = $url . '?foo=param';
        $expectedOptions = ['foo' => 'option', 'default' => 'value'];
        $statusCode = 403;
        $reasonPhrase = 'Forbidden';

        $response = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();

        $request = $this->createMock(RequestInterface::class);

        $this->sourceClient->expects(static::once())
            ->method('createRequest')
            ->with('get', $expectedUrl, $headers, null, $expectedOptions)
            ->willReturn($request);

        $request->expects(static::once())->method('send')->willReturn($response);
        $request->expects(static::atLeastOnce())->method('getUrl')->willReturn($url);

        $response->expects(static::once())->method('isSuccessful')->willReturn(false);
        $response->expects(static::atLeastOnce())->method('getStatusCode')->willReturn($statusCode);
        $response->expects(static::atLeastOnce())->method('getReasonPhrase')->willReturn($reasonPhrase);

        $this->expectException(GuzzleRestException::class);
        $this->expectExceptionMessage(
            "Unsuccessful response" . PHP_EOL .
            "[status code] $statusCode" . PHP_EOL .
            "[reason phrase] $reasonPhrase" . PHP_EOL .
            "[url] $url"
        );

        $getter = 'get' . \strtoupper($format);

        $this->client->$getter($url, $params, $headers, $options);
    }

    public function getFormattedResultDataProvider()
    {
        return [
            'json' => [
                'format' => 'json',
            ]
        ];
    }
}
