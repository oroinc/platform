<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Client;

use Gos\Component\WebSocketClient\Exception\BadResponseException;
use Gos\Component\WebSocketClient\Exception\WebsocketException;
use Oro\Bundle\SyncBundle\Client\LoggerAwareWebsocketClientDecorator;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;
use Oro\Bundle\SyncBundle\Exception\ValidationFailedException;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;

class LoggerAwareWebsocketClientDecoratorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    /**
     * @var WebsocketClientInterface|\PHPUnit\Framework\MockObject\MockObject
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
        $connectionSession = 'sampleSession';

        $this->decoratedClient
            ->expects(self::once())
            ->method('connect')
            ->willReturn($connectionSession);

        $this->assertLoggerDebugMethodCalled();

        self::assertSame($connectionSession, $this->loggerAwareClientDecorator->connect());
    }

    public function testConnectWithException()
    {
        $this->decoratedClient
            ->expects(self::once())
            ->method('connect')
            ->willThrowException(new WebsocketException());

        $this->assertLoggerErrorMethodCalled();

        self::assertNull($this->loggerAwareClientDecorator->connect());
    }

    public function testDisconnect()
    {
        $this->decoratedClient
            ->expects(self::once())
            ->method('disconnect')
            ->willReturn(true);

        $this->assertLoggerDebugMethodCalled();

        self::assertTrue($this->loggerAwareClientDecorator->disconnect());
    }

    public function testDisconnectFailed()
    {
        $this->decoratedClient
            ->expects(self::once())
            ->method('disconnect')
            ->willReturn(false);

        $this->assertLoggerNotCalled();

        self::assertFalse($this->loggerAwareClientDecorator->disconnect());
    }

    public function testIsConnected()
    {
        $this->decoratedClient
            ->expects(self::once())
            ->method('isConnected')
            ->willReturn(true);

        self::assertTrue($this->loggerAwareClientDecorator->isConnected());
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

    /**
     * @dataProvider exceptionDataProvider
     *
     * @param \Exception $exception
     */
    public function testPublishWithException(\Exception $exception)
    {
        $topicUri = 'sampleUri';
        $payload = 'samplePayload';
        $exclude = ['sampleExclude'];
        $eligible = ['sampleEligible'];

        $this->decoratedClient
            ->expects(self::once())
            ->method('publish')
            ->with($topicUri, $payload, $exclude, $eligible)
            ->willThrowException($exception);

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

    public function testPrefixWithBadResponseException()
    {
        $exception = new BadResponseException();

        $this->decoratedClient
            ->expects(self::once())
            ->method('prefix')
            ->willThrowException($exception);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Error occurred while communicating with websocket server', [$exception]);

        self::assertFalse($this->loggerAwareClientDecorator->prefix('samplePrefix', 'sampleUrl'));
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

        self::assertTrue($this->loggerAwareClientDecorator->event($topicUri, $payload));
    }

    /**
     * @dataProvider exceptionDataProvider
     *
     * @param \Exception $exception
     */
    public function testEventWithException(\Exception $exception)
    {
        $topicUri = 'sampleUri';
        $payload = 'samplePayload';

        $this->decoratedClient
            ->expects(self::once())
            ->method('event')
            ->with($topicUri, $payload)
            ->willThrowException($exception);

        $this->assertLoggerErrorMethodCalled();

        self::assertFalse($this->loggerAwareClientDecorator->event($topicUri, $payload));
    }

    /**
     * @return array
     */
    public function exceptionDataProvider(): array
    {
        return [
            'BadResponseException' => [new BadResponseException()],
            'WebsocketException' => [new WebsocketException()],
            'ValidationFailedException' => [new ValidationFailedException()],
        ];
    }
}
