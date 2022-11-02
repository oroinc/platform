<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider\Rest\Client\Guzzle;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestClient;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestException;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestResponse;
use Oro\Component\Testing\ReflectionUtil;
use Psr\Http\Message\StreamInterface;

class GuzzleRestClientTest extends \PHPUnit\Framework\TestCase
{
    private const BASE_URL = 'https://example.com/api/';
    private const DEFAULT_OPTIONS = ['default' => 'value'];

    /** @var Client|\PHPUnit\Framework\MockObject\MockObject */
    private $sourceClient;

    /** @var GuzzleRestClient */
    private $client;

    protected function setUp(): void
    {
        $this->sourceClient = $this->createMock(Client::class);

        $this->client = new GuzzleRestClient(self::BASE_URL, self::DEFAULT_OPTIONS);
        $this->client->setGuzzleClient($this->sourceClient);
    }

    public function testConstructor()
    {
        self::assertEquals(self::BASE_URL, ReflectionUtil::getPropertyValue($this->client, 'baseUrl'));
        self::assertEquals(self::DEFAULT_OPTIONS, ReflectionUtil::getPropertyValue($this->client, 'defaultOptions'));
    }

    public function testGetLastResponseWorks()
    {
        $response = $this->client->get('users');

        self::assertEquals($response, $this->client->getLastResponse());
    }

    /**
     * @dataProvider performRequestDataProvider
     */
    public function testPerformRequestWorks(string $method, array $args, array $expected)
    {
        $response = $this->createMock(Response::class);

        $this->sourceClient->expects(self::once())
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

        self::assertInstanceOf(GuzzleRestResponse::class, $actual);
        self::assertEquals($response, $actual->getSourceResponse());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function performRequestDataProvider(): array
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

        $this->sourceClient->expects(self::once())
            ->method('send')
            ->willThrowException(new \Exception('Exception message'));

        $this->client->performRequest($method, $url);
    }

    public function testGetFormattedResult()
    {
        $url = 'https://example.com/api/v2/users.json';
        $params = ['foo' => 'param'];
        $headers = ['foo' => 'header'];
        $options = ['foo' => 'option'];
        $expectedResult = ['foo' => 'data'];

        $response = $this->createMock(Response::class);

        $this->sourceClient->expects(self::once())
            ->method('send')
            ->willReturn($response);

        $response->expects(self::any())
            ->method('getStatusCode')
            ->willReturn(200);

        $stream = fopen('php://memory', 'rb+');
        fwrite($stream, '{"foo":"data"}');
        rewind($stream);

        $response->expects(self::once())
            ->method('getBody')
            ->willReturn(new Stream($stream));

        self::assertEquals($expectedResult, $this->client->getJSON($url, $params, $headers, $options));
    }

    public function testGetFormattedResultThrowException()
    {
        $url = 'https://example.com/api/v2/users.json';
        $params = ['foo' => 'param'];
        $headers = ['foo' => 'header'];
        $options = ['foo' => 'option'];
        $statusCode = 403;
        $reasonPhrase = 'Forbidden';

        $response = $this->createMock(Response::class);

        $this->sourceClient->expects(self::once())
            ->method('send')
            ->willReturn($response);

        $response->expects(self::atLeastOnce())
            ->method('getStatusCode')
            ->willReturn($statusCode);
        $response->expects(self::atLeastOnce())
            ->method('getReasonPhrase')
            ->willReturn($reasonPhrase);
        $body = $this->createMock(StreamInterface::class);
        $body->expects($this->atLeastOnce())
            ->method('isSeekable')
            ->willReturn(false);
        $response->expects(self::atLeastOnce())
            ->method('getBody')
            ->willReturn($body);

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
