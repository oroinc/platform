<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Client;

use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;
use Oro\Bundle\SyncBundle\Tests\Unit\Client\Stub\WebsocketClientDecoratorStub;

class AbstractWebsocketClientDecoratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebsocketClientInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $decoratedClient;

    /**
     * @var WebsocketClientDecoratorStub
     */
    private $clientDecorator;

    protected function setUp()
    {
        $this->decoratedClient = $this->createMock(WebsocketClientInterface::class);
        $this->clientDecorator = new WebsocketClientDecoratorStub($this->decoratedClient);
    }

    public function testConnect()
    {
        $connectionSession = 'connectionSession';
        $target = 'sampleTarget';
        $this->decoratedClient
            ->expects(self::once())
            ->method('connect')
            ->with($target)
            ->willReturn($connectionSession);

        self::assertSame($connectionSession, $this->clientDecorator->connect($target));
    }

    public function testDisconnect()
    {
        $this->decoratedClient
            ->expects(self::once())
            ->method('disconnect')
            ->willReturn(true);

        self::assertSame(true, $this->clientDecorator->disconnect());
    }

    public function testIsConnected()
    {
        $this->decoratedClient
            ->expects(self::once())
            ->method('isConnected')
            ->willReturn(true);

        self::assertSame(true, $this->clientDecorator->isConnected());
    }

    public function testPublish()
    {
        $topicUri = 'sampleUri';
        $payload = 'samplePayload';
        $exclude = ['sampleExclude'];
        $eligible = ['sampleEligible'];

        $this->decoratedClient
            ->expects(self::once())
            ->method('publish')
            ->with($topicUri, $payload, $exclude, $eligible)
            ->willReturn(true);

        self::assertTrue($this->clientDecorator->publish($topicUri, $payload, $exclude, $eligible));
    }

    public function testPrefix()
    {
        $prefix = 'samplePrefix';
        $uri = 'sampleUri';

        $this->decoratedClient
            ->expects(self::once())
            ->method('prefix')
            ->with($prefix, $uri)
            ->willReturn(true);

        self::assertTrue($this->clientDecorator->prefix($prefix, $uri));
    }

    public function testCall()
    {
        $procUri = 'sampleUri';
        $arguments = ['sampleArgument'];

        $this->decoratedClient
            ->expects(self::once())
            ->method('call')
            ->with($procUri, $arguments)
            ->willReturn(true);

        self::assertTrue($this->clientDecorator->call($procUri, $arguments));
    }

    public function testEvent()
    {
        $topicUri = 'sampleUri';
        $payload = 'samplePayload';

        $this->decoratedClient
            ->expects(self::once())
            ->method('event')
            ->with($topicUri, $payload)
            ->willReturn(true);

        self::assertSame(true, $this->clientDecorator->event($topicUri, $payload));
    }
}
