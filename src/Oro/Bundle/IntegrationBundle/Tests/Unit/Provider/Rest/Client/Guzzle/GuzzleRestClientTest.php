<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider\Rest\Client\Guzzle;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestClient;

class GuzzleRestClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $sourceClient;

    /**
     * @var GuzzleRestClient
     */
    protected $client;

    /**
     * @var string
     */
    protected $baseUrl = 'https://example.com/api';

    /**
     * @var array
     */
    protected $defaultOptions = array('default' => 'value');

    protected function setUp()
    {
        $this->sourceClient = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $this->client = new GuzzleRestClient($this->baseUrl, $this->defaultOptions);
        $this->client->setGuzzleClient($this->sourceClient);
    }

    public function testConstructor()
    {
        $this->assertAttributeEquals($this->baseUrl, 'baseUrl', $this->client);
        $this->assertAttributeEquals($this->defaultOptions, 'defaultOptions', $this->client);
    }

    public function testGetLastResponseWorks()
    {
        $request = $this->getMock('Guzzle\\Http\\Message\\RequestInterface');
        $this->sourceClient->expects($this->once())
            ->method('createRequest')
            ->will($this->returnValue($request));

        $response = $this->getMockBuilder('Guzzle\\Http\\Message\\Response')
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->once())
            ->method('send')
            ->will($this->returnValue($response));

        $response = $this->client->get('users');
        $this->assertEquals($response, $this->client->getLastResponse());
    }

    /**
     * @dataProvider performRequestDataProvider
     */
    public function testPerformRequestWorks($method, $args, $expected)
    {
        $request = $this->getMock('Guzzle\\Http\\Message\\RequestInterface');
        $this->sourceClient->expects($this->once())
            ->method('createRequest')
            ->with($expected['method'], $expected['url'], $expected['headers'], $expected['data'], $expected['options'])
            ->will($this->returnValue($request));

        $response = $this->getMockBuilder('Guzzle\\Http\\Message\\Response')
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->once())
            ->method('send')
            ->will($this->returnValue($response));

        $actual = call_user_func_array(array($this->client, $method), $args);
        $this->assertInstanceOf(
            'Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestResponse',
            $actual
        );
        $this->assertEquals($response, $actual->getSourceResponse());
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

    /**
     * @expectedException \Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestException
     * @expectedExceptionMessage REST error: Exception message
     * [url] https://google.com/api/v2
     * [method] get
     */
    public function testPerformRequestThrowException()
    {
        $method = 'get';
        $url = 'https://google.com/api/v2';

        $request = $this->getMock('Guzzle\\Http\\Message\\RequestInterface');

        $this->sourceClient->expects($this->once())
            ->method('createRequest')
            ->will($this->returnValue($request));

        $request->expects($this->once())
            ->method('send')
            ->will($this->throwException(new \Exception('Exception message')));
        $request->expects($this->once())
            ->method('getUrl')
            ->will($this->returnValue($url));
        $request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue($method));

        $this->client->performRequest($method, $url);
    }
}
