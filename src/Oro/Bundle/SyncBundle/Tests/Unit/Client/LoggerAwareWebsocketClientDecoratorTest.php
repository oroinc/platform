<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Client;

use Gos\Component\WebSocketClient\Exception\BadResponseException;
use Gos\Component\WebSocketClient\Exception\WebsocketException;
use Oro\Bundle\SyncBundle\Client\LoggerAwareWebsocketClientDecorator;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;
use Oro\Bundle\SyncBundle\Exception\ValidationFailedException;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LoggerAwareWebsocketClientDecoratorTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private WebsocketClientInterface&MockObject $decoratedClient;
    private LoggerAwareWebsocketClientDecorator $loggerAwareClientDecorator;

    #[\Override]
    protected function setUp(): void
    {
        $this->decoratedClient = $this->createMock(WebsocketClientInterface::class);

        $this->loggerAwareClientDecorator = new LoggerAwareWebsocketClientDecorator($this->decoratedClient);
        $this->setUpLoggerMock($this->loggerAwareClientDecorator);
    }

    public function testConnect(): void
    {
        $connectionSession = 'sampleSession';

        $this->decoratedClient->expects(self::once())
            ->method('connect')
            ->willReturn($connectionSession);

        $this->assertLoggerDebugMethodCalled();

        self::assertSame($connectionSession, $this->loggerAwareClientDecorator->connect());
    }

    public function testConnectWithException(): void
    {
        $this->decoratedClient->expects(self::once())
            ->method('connect')
            ->willThrowException(new WebsocketException());

        $this->assertLoggerErrorMethodCalled();

        self::assertNull($this->loggerAwareClientDecorator->connect());
    }

    public function testDisconnect(): void
    {
        $this->decoratedClient->expects(self::once())
            ->method('disconnect')
            ->willReturn(true);

        $this->assertLoggerDebugMethodCalled();

        self::assertTrue($this->loggerAwareClientDecorator->disconnect());
    }

    public function testDisconnectFailed(): void
    {
        $this->decoratedClient->expects(self::once())
            ->method('disconnect')
            ->willReturn(false);

        $this->assertLoggerNotCalled();

        self::assertFalse($this->loggerAwareClientDecorator->disconnect());
    }

    public function testIsConnected(): void
    {
        $this->decoratedClient->expects(self::once())
            ->method('isConnected')
            ->willReturn(true);

        self::assertTrue($this->loggerAwareClientDecorator->isConnected());
    }

    public function testPublish(): void
    {
        $topicUri = 'sampleUri';
        $payload = 'samplePayload';
        $exclude = ['sampleExclude'];
        $eligible = ['sampleEligible'];

        $this->decoratedClient->expects(self::once())
            ->method('publish')
            ->with($topicUri, $payload, $exclude, $eligible)
            ->willReturn(true);

        $this->assertLoggerDebugMethodCalled();

        self::assertTrue($this->loggerAwareClientDecorator->publish($topicUri, $payload, $exclude, $eligible));
    }

    /**
     * @dataProvider exceptionDataProvider
     */
    public function testPublishWithException(\Exception $exception): void
    {
        $topicUri = 'sampleUri';
        $payload = 'samplePayload';
        $exclude = ['sampleExclude'];
        $eligible = ['sampleEligible'];

        $this->decoratedClient->expects(self::once())
            ->method('publish')
            ->with($topicUri, $payload, $exclude, $eligible)
            ->willThrowException($exception);

        $this->assertLoggerErrorMethodCalled();

        self::assertFalse($this->loggerAwareClientDecorator->publish($topicUri, $payload, $exclude, $eligible));
    }

    public function testPrefix(): void
    {
        $prefix = 'samplePrefix';
        $uri = 'sampleUri';

        $this->decoratedClient->expects(self::once())
            ->method('prefix')
            ->with($prefix, $uri)
            ->willReturn(true);

        $this->assertLoggerDebugMethodCalled();

        self::assertTrue($this->loggerAwareClientDecorator->prefix($prefix, $uri));
    }

    public function testPrefixWithException(): void
    {
        $prefix = 'samplePrefix';
        $uri = 'sampleUri';

        $this->decoratedClient->expects(self::once())
            ->method('prefix')
            ->with($prefix, $uri)
            ->willThrowException(new WebsocketException());

        $this->assertLoggerErrorMethodCalled();

        self::assertFalse($this->loggerAwareClientDecorator->prefix($prefix, $uri));
    }

    public function testPrefixWithBadResponseException(): void
    {
        $exception = new BadResponseException();

        $this->decoratedClient->expects(self::once())
            ->method('prefix')
            ->willThrowException($exception);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Error occurred while communicating with websocket server', [$exception]);

        self::assertFalse($this->loggerAwareClientDecorator->prefix('samplePrefix', 'sampleUrl'));
    }

    public function testCall(): void
    {
        $procUri = 'sampleUri';
        $arguments = ['sampleArgument'];

        $this->decoratedClient->expects(self::once())
            ->method('call')
            ->with($procUri, $arguments)
            ->willReturn(true);

        $this->assertLoggerDebugMethodCalled();

        self::assertTrue($this->loggerAwareClientDecorator->call($procUri, $arguments));
    }

    public function testCallWithException(): void
    {
        $procUri = 'sampleUri';
        $arguments = ['sampleArgument'];

        $this->decoratedClient->expects(self::once())
            ->method('call')
            ->with($procUri, $arguments)
            ->willThrowException(new WebsocketException());

        $this->assertLoggerErrorMethodCalled();

        self::assertFalse($this->loggerAwareClientDecorator->call($procUri, $arguments));
    }

    public function testEvent(): void
    {
        $topicUri = 'sampleUri';
        $payload = 'samplePayload';

        $this->decoratedClient->expects(self::once())
            ->method('event')
            ->with($topicUri, $payload)
            ->willReturn(true);

        $this->assertLoggerDebugMethodCalled();

        self::assertTrue($this->loggerAwareClientDecorator->event($topicUri, $payload));
    }

    /**
     * @dataProvider exceptionDataProvider
     */
    public function testEventWithException(\Exception $exception): void
    {
        $topicUri = 'sampleUri';
        $payload = 'samplePayload';

        $this->decoratedClient->expects(self::once())
            ->method('event')
            ->with($topicUri, $payload)
            ->willThrowException($exception);

        $this->assertLoggerErrorMethodCalled();

        self::assertFalse($this->loggerAwareClientDecorator->event($topicUri, $payload));
    }

    public function exceptionDataProvider(): array
    {
        return [
            'BadResponseException' => [new BadResponseException()],
            'WebsocketException' => [new WebsocketException()],
            'ValidationFailedException' => [new ValidationFailedException()],
        ];
    }
}
