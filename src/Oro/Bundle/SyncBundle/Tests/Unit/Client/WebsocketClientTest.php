<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Client;

use Oro\Bundle\SyncBundle\Client\WebsocketClient;
use Oro\Bundle\SyncBundle\Client\Factory\GosClientFactoryInterface;
use Gos\Component\WebSocketClient\Wamp\Client as GosClient;

class WebsocketClientTest extends \PHPUnit_Framework_TestCase
{
    private const WS_HOST = 'testHost';
    private const WS_PORT = 'testPort';
    private const WS_SECURED = true;
    private const WS_ORIGIN = 'testOrigin';

    /**
     * @var GosClientFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $gosClientFactory;

    /**
     * @var GosClient|\PHPUnit_Framework_MockObject_MockObject
     */
    private $gosClient;

    /**
     * @var WebsocketClient
     */
    private $client;

    protected function setUp()
    {
        $this->gosClientFactory = $this->createMock(GosClientFactoryInterface::class);
        $this->client = new WebsocketClient(
            $this->gosClientFactory,
            self::WS_HOST,
            self::WS_PORT,
            self::WS_SECURED,
            self::WS_ORIGIN
        );

        $this->gosClient = $this->createMock(GosClient::class);
        $this->gosClientFactory
            ->expects(self::any())
            ->method('createGosClient')
            ->with(self::WS_HOST, self::WS_PORT, self::WS_SECURED, self::WS_ORIGIN)
            ->willReturn($this->gosClient);
    }

    public function testConnect()
    {
        $connectionSession = 'connectionSession';
        $target = 'sampleTarget';
        $this->gosClient
            ->expects(self::once())
            ->method('connect')
            ->with($target)
            ->willReturn($connectionSession);

        self::assertSame($connectionSession, $this->client->connect($target));
    }

    public function testDisconnect()
    {
        $this->gosClient
            ->expects(self::once())
            ->method('disconnect')
            ->willReturn(true);

        self::assertTrue($this->client->disconnect());
    }

    public function testIsConnected()
    {
        $this->gosClient
            ->expects(self::once())
            ->method('isConnected')
            ->willReturn(true);

        self::assertTrue($this->client->isConnected());
    }

    public function testPublish()
    {
        $topicUri = 'sampleUri';
        $payload = 'samplePayload';
        $exclude = ['sampleExclude'];
        $eligible = ['sampleEligible'];

        $this->gosClient
            ->expects(self::once())
            ->method('publish')
            ->with($topicUri, $payload, $exclude, $eligible)
            ->willReturn(true);

        self::assertTrue($this->client->publish($topicUri, $payload, $exclude, $eligible));
    }

    public function testPublichFail()
    {
        $this->gosClient->expects($this->never())->method('publish');
        $this->expectException(
            \InvalidArgumentException::class,
            'Malformed UTF-8 characters, possibly incorrectly encoded'
        );
        $this->client->publish('sampleUrl', "\xB1\x31");
    }

    public function testPrefix()
    {
        $prefix = 'samplePrefix';
        $uri = 'sampleUri';

        $this->gosClient
            ->expects(self::once())
            ->method('prefix')
            ->with($prefix, $uri)
            ->willReturn(true);

        self::assertTrue($this->client->prefix($prefix, $uri));
    }

    public function testCall()
    {
        $procUri = 'sampleUri';
        $arguments = ['sampleArgument'];

        $this->gosClient
            ->expects(self::once())
            ->method('call')
            ->with($procUri, $arguments)
            ->willReturn(true);

        self::assertTrue($this->client->call($procUri, $arguments));
    }

    public function testEvent()
    {
        $topicUri = 'sampleUri';
        $payload = 'samplePayload';

        $this->gosClient
            ->expects(self::once())
            ->method('event')
            ->with($topicUri, $payload)
            ->willReturn(true);

        self::assertTrue($this->client->event($topicUri, $payload));
    }
}
