<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Client;

use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketProviderInterface;
use Oro\Bundle\SyncBundle\Client\WebsocketClient;
use Oro\Bundle\SyncBundle\Client\Factory\GosClientFactoryInterface;
use Gos\Component\WebSocketClient\Wamp\Client as GosClient;
use Oro\Bundle\SyncBundle\Exception\ValidationFailedException;

class WebsocketClientTest extends \PHPUnit_Framework_TestCase
{
    private const WS_HOST = 'testHost';
    private const WS_PORT = 'testPort';
    private const WS_SECURED = true;
    private const WS_ORIGIN = 'testOrigin';
    private const TICKET = 'sampleTicket';

    /** @var GosClientFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $gosClientFactory;

    /** @var TicketProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $ticketProvider;

    /** @var GosClient|\PHPUnit_Framework_MockObject_MockObject */
    private $gosClient;

    /** @var WebsocketClient */
    private $client;

    protected function setUp()
    {
        $this->gosClientFactory = $this->createMock(GosClientFactoryInterface::class);
        $this->ticketProvider = $this->createMock(TicketProviderInterface::class);

        $this->client = new WebsocketClient(
            $this->gosClientFactory,
            $this->ticketProvider,
            self::WS_HOST,
            self::WS_PORT,
            self::WS_SECURED,
            self::WS_ORIGIN
        );

        $this->gosClient = $this->createMock(GosClient::class);
    }

    /**
     * @dataProvider connectDataProvider
     *
     * @param string $target
     * @param string $expectedTarget
     */
    public function testConnect(string $target, string $expectedTarget)
    {
        $connectionSession = 'sampleSession';

        $this->mockGosClientFactory();
        $this->gosClient
            ->expects(self::once())
            ->method('connect')
            ->with($expectedTarget)
            ->willReturn($connectionSession);

        $this->ticketProvider
            ->expects(self::once())
            ->method('generateTicket')
            ->willReturn(self::TICKET);

        self::assertSame($connectionSession, $this->client->connect($target));
    }

    /**
     * @return array
     */
    public function connectDataProvider(): array
    {
        return [
            'empty path in target' => [
                'target' => '',
                'expectedTarget' => '?ticket=' . self::TICKET,
            ],

            'root target' => [
                'target' => '/',
                'expectedTarget' => '/?ticket=' . self::TICKET,
            ],

            'normal path' => [
                'target' => '/sample-path',
                'expectedTarget' => '/sample-path?ticket=' . self::TICKET,
            ],

            'normal path with query' => [
                'target' => '/sample-path?fooParam=bar',
                'expectedTarget' => '/sample-path?fooParam=bar&ticket=' . self::TICKET,
            ],
        ];
    }

    public function testDisconnect()
    {
        $this->mockGosClientFactory();
        $this->gosClient
            ->expects(self::once())
            ->method('disconnect')
            ->willReturn(true);

        self::assertTrue($this->client->disconnect());
    }

    public function testIsConnected()
    {
        $this->mockGosClientFactory();
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

        $this->mockGosClientFactory();
        $this->gosClient
            ->expects(self::once())
            ->method('isConnected')
            ->willReturn(false);
        $this->gosClient
            ->expects(self::once())
            ->method('connect');
        $this->gosClient
            ->expects(self::once())
            ->method('publish')
            ->with($topicUri, $payload, $exclude, $eligible)
            ->willReturn(true);

        self::assertTrue($this->client->publish($topicUri, $payload, $exclude, $eligible));
    }

    public function testPublishValidationFailure()
    {
        $this->gosClientFactory
            ->expects(self::never())
            ->method('createGosClient');

        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('Malformed UTF-8 characters, possibly incorrectly encoded');

        $this->client->publish('sampleUrl', "\xB1\x31");
    }

    public function testPrefix()
    {
        $prefix = 'samplePrefix';
        $uri = 'sampleUri';

        $this->mockGosClientFactory();
        $this->gosClient
            ->expects(self::once())
            ->method('isConnected')
            ->willReturn(false);
        $this->gosClient
            ->expects(self::once())
            ->method('connect');
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

        $this->mockGosClientFactory();
        $this->gosClient
            ->expects(self::once())
            ->method('isConnected')
            ->willReturn(false);
        $this->gosClient
            ->expects(self::once())
            ->method('connect');
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

        $this->mockGosClientFactory();
        $this->gosClient
            ->expects(self::once())
            ->method('isConnected')
            ->willReturn(false);
        $this->gosClient
            ->expects(self::once())
            ->method('connect');
        $this->gosClient
            ->expects(self::once())
            ->method('event')
            ->with($topicUri, $payload)
            ->willReturn(true);

        self::assertTrue($this->client->event($topicUri, $payload));
    }

    public function testEventValidationFailure()
    {
        $this->gosClientFactory
            ->expects(self::never())
            ->method('createGosClient');

        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('Malformed UTF-8 characters, possibly incorrectly encoded');

        $this->client->event('sampleUrl', "\xB1\x31");
    }

    private function mockGosClientFactory(): void
    {
        $this->gosClientFactory
            ->expects(self::once())
            ->method('createGosClient')
            ->with(self::WS_HOST, self::WS_PORT, self::WS_SECURED, self::WS_ORIGIN)
            ->willReturn($this->gosClient);
    }
}
