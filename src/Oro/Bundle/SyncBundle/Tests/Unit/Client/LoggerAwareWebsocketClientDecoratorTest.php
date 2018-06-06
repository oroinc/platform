<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Client;

use Gos\Component\WebSocketClient\Exception\WebsocketException;
use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketProviderInterface;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;
use Oro\Bundle\SyncBundle\Client\LoggerAwareWebsocketClientDecorator;
use Oro\Bundle\SyncBundle\Client\TicketAuthenticationAwareWebsocketClientDecorator;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;

class LoggerAwareWebsocketClientDecoratorTest extends \PHPUnit_Framework_TestCase
{
    use LoggerAwareTraitTestTrait;

    /**
     * @var WebsocketClientInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $decoratedClient;

    /**
     * @var LoggerAwareWebsocketClientDecorator
     */
    private $loggerAwareClientDecorator;

    protected function setUp()
    {
        $this->decoratedClient = $this->createMock(WebsocketClientInterface::class);

        $this->loggerAwareClientDecorator = new LoggerAwareWebsocketClientDecorator($this->decoratedClient);

        $this->setUpLoggerMock($this->loggerAwareClientDecorator);
    }

    public function testConnect()
    {
        $target = 'sampleTarget';
        $connectionSession = 'sampleSession';

        $this->decoratedClient
            ->expects(self::once())
            ->method('connect')
            ->with($target)
            ->willReturn($connectionSession);

        $this->assertLoggerDebugMethodCalled();

        self::assertSame($connectionSession, $this->loggerAwareClientDecorator->connect($target));
    }

    public function testConnectWithException()
    {
        $target = 'sampleTarget';

        $this->decoratedClient
            ->expects(self::once())
            ->method('connect')
            ->with($target)
            ->willThrowException(new WebsocketException());

        $this->assertLoggerErrorMethodCalled();

        self::assertNull($this->loggerAwareClientDecorator->connect($target));
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

        $this->assertLoggerDebugMethodCalled();

        self::assertTrue($this->loggerAwareClientDecorator->publish($topicUri, $payload, $exclude, $eligible));
    }

    public function testPublishWithException()
    {
        $topicUri = 'sampleUri';
        $payload = 'samplePayload';
        $exclude = ['sampleExclude'];
        $eligible = ['sampleEligible'];

        $this->decoratedClient
            ->expects(self::once())
            ->method('publish')
            ->with($topicUri, $payload, $exclude, $eligible)
            ->willThrowException(new WebsocketException());

        $this->assertLoggerErrorMethodCalled();

        self::assertFalse($this->loggerAwareClientDecorator->publish($topicUri, $payload, $exclude, $eligible));
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

        $this->assertLoggerDebugMethodCalled();

        self::assertTrue($this->loggerAwareClientDecorator->prefix($prefix, $uri));
    }

    public function testPrefixWithException()
    {
        $prefix = 'samplePrefix';
        $uri = 'sampleUri';

        $this->decoratedClient
            ->expects(self::once())
            ->method('prefix')
            ->with($prefix, $uri)
            ->willThrowException(new WebsocketException());

        $this->assertLoggerErrorMethodCalled();

        self::assertFalse($this->loggerAwareClientDecorator->prefix($prefix, $uri));
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

        $this->assertLoggerDebugMethodCalled();

        self::assertTrue($this->loggerAwareClientDecorator->call($procUri, $arguments));
    }

    public function testCallWithException()
    {
        $procUri = 'sampleUri';
        $arguments = ['sampleArgument'];

        $this->decoratedClient
            ->expects(self::once())
            ->method('call')
            ->with($procUri, $arguments)
            ->willThrowException(new WebsocketException());

        $this->assertLoggerErrorMethodCalled();

        self::assertFalse($this->loggerAwareClientDecorator->call($procUri, $arguments));
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

        $this->assertLoggerDebugMethodCalled();

        self::assertSame(true, $this->loggerAwareClientDecorator->event($topicUri, $payload));
    }

    public function testEventWithException()
    {
        $topicUri = 'sampleUri';
        $payload = 'samplePayload';

        $this->decoratedClient
            ->expects(self::once())
            ->method('event')
            ->with($topicUri, $payload)
            ->willThrowException(new WebsocketException());

        $this->assertLoggerErrorMethodCalled();

        self::assertFalse($this->loggerAwareClientDecorator->event($topicUri, $payload));
    }
}
