<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Client;

use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketProviderInterface;
use Oro\Bundle\SyncBundle\Client\Wamp\Factory\WampClientFactoryInterface;
use Oro\Bundle\SyncBundle\Client\Wamp\WampClient;
use Oro\Bundle\SyncBundle\Client\WebsocketClient;
use Oro\Bundle\SyncBundle\Exception\ValidationFailedException;
use Oro\Bundle\SyncBundle\Provider\WebsocketClientParametersProviderInterface;

class WebsocketClientTest extends \PHPUnit\Framework\TestCase
{
    private const TICKET = 'sampleTicket';

    /** @var WampClientFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $wampClientFactory;

    /** @var WebsocketClientParametersProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $clientParametersProvider;

    /** @var TicketProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $ticketProvider;

    /** @var WampClient|\PHPUnit\Framework\MockObject\MockObject */
    private $wampClient;

    /** @var WebsocketClient */
    private $websocketClient;

    protected function setUp(): void
    {
        $this->wampClientFactory = $this->createMock(WampClientFactoryInterface::class);
        $this->clientParametersProvider = $this->createMock(WebsocketClientParametersProviderInterface::class);
        $this->ticketProvider = $this->createMock(TicketProviderInterface::class);

        $this->websocketClient = new WebsocketClient(
            $this->wampClientFactory,
            $this->clientParametersProvider,
            $this->ticketProvider
        );

        $this->wampClient = $this->createMock(WampClient::class);
    }

    /**
     * @dataProvider connectDataProvider
     */
    public function testConnect(string $target, string $expectedTarget)
    {
        $connectionSession = 'sampleSession';

        $this->clientParametersProvider->expects(self::once())
            ->method('getPath')
            ->willReturn($target);

        $this->mockClientFactory();
        $this->wampClient->expects(self::once())
            ->method('connect')
            ->with($expectedTarget)
            ->willReturn($connectionSession);

        $this->ticketProvider->expects(self::once())
            ->method('generateTicket')
            ->willReturn(self::TICKET);

        self::assertSame($connectionSession, $this->websocketClient->connect());
    }

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

            'path without slash' => [
                'target' => 'sample-path',
                'expectedTarget' => 'sample-path?ticket=' . self::TICKET,
            ],

            'normal path with query' => [
                'target' => '/sample-path?fooParam=bar',
                'expectedTarget' => '/sample-path?fooParam=bar&ticket=' . self::TICKET,
            ],
        ];
    }

    public function testDisconnect()
    {
        $this->mockClientFactory();
        $this->wampClient->expects(self::once())
            ->method('disconnect')
            ->willReturn(true);

        self::assertTrue($this->websocketClient->disconnect());
    }

    public function testIsConnected()
    {
        $this->mockClientFactory();
        $this->wampClient->expects(self::once())
            ->method('isConnected')
            ->willReturn(true);

        self::assertTrue($this->websocketClient->isConnected());
    }

    public function testPublish()
    {
        $topicUri = 'sampleUri';
        $payload = 'samplePayload';
        $exclude = ['sampleExclude'];
        $eligible = ['sampleEligible'];

        $this->mockClientFactory();
        $this->wampClient->expects(self::once())
            ->method('isConnected')
            ->willReturn(false);
        $this->wampClient->expects(self::once())
            ->method('connect');
        $this->wampClient->expects(self::once())
            ->method('publish')
            ->with($topicUri, json_encode($payload), $exclude, $eligible);

        self::assertTrue($this->websocketClient->publish($topicUri, $payload, $exclude, $eligible));
    }

    public function testPublishValidationFailure()
    {
        $this->wampClientFactory->expects(self::never())
            ->method('createClient');

        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('Malformed UTF-8 characters, possibly incorrectly encoded');

        $this->websocketClient->publish('sampleUrl', "\xB1\x31");
    }

    public function testPrefix()
    {
        $prefix = 'samplePrefix';
        $uri = 'sampleUri';

        $this->mockClientFactory();
        $this->wampClient->expects(self::once())
            ->method('isConnected')
            ->willReturn(false);
        $this->wampClient->expects(self::once())
            ->method('connect');
        $this->wampClient->expects(self::once())
            ->method('prefix')
            ->with($prefix, $uri);

        self::assertTrue($this->websocketClient->prefix($prefix, $uri));
    }

    public function testCall()
    {
        $procUri = 'sampleUri';
        $arguments = ['sampleArgument'];

        $this->mockClientFactory();
        $this->wampClient->expects(self::once())
            ->method('isConnected')
            ->willReturn(false);
        $this->wampClient->expects(self::once())
            ->method('connect');
        $this->wampClient->expects(self::once())
            ->method('call')
            ->with($procUri, $arguments);

        self::assertTrue($this->websocketClient->call($procUri, $arguments));
    }

    public function testEvent()
    {
        $topicUri = 'sampleUri';
        $payload = 'samplePayload';

        $this->mockClientFactory();
        $this->wampClient->expects(self::once())
            ->method('isConnected')
            ->willReturn(false);
        $this->wampClient->expects(self::once())
            ->method('connect');
        $this->wampClient->expects(self::once())
            ->method('event')
            ->with($topicUri, json_encode($payload));

        self::assertTrue($this->websocketClient->event($topicUri, $payload));
    }

    public function testEventValidationFailure()
    {
        $this->wampClientFactory->expects(self::never())
            ->method('createClient');

        $this->expectException(ValidationFailedException::class);
        $this->expectExceptionMessage('Malformed UTF-8 characters, possibly incorrectly encoded');

        $this->websocketClient->event('sampleUrl', "\xB1\x31");
    }

    private function mockClientFactory(): void
    {
        $this->wampClientFactory->expects(self::once())
            ->method('createClient')
            ->with($this->clientParametersProvider)
            ->willReturn($this->wampClient);
    }
}
