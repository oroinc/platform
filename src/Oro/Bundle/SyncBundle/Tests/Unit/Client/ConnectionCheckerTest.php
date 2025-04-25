<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Client;

use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\SyncBundle\Client\ConnectionChecker;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;
use Oro\Bundle\SyncBundle\Provider\WebsocketClientParametersProviderInterface;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ConnectionCheckerTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private WebsocketClientInterface|MockObject $client;

    private ApplicationState|MockObject $applicationState;

    private WebsocketClientParametersProviderInterface|MockObject $websocketClientParametersProvider;

    private ConnectionChecker $checker;

    protected function setUp(): void
    {
        $this->client = $this->createMock(WebsocketClientInterface::class);
        $this->applicationState = $this->createMock(ApplicationState::class);
        $this->websocketClientParametersProvider = $this->createMock(WebsocketClientParametersProviderInterface::class);

        $this->checker = new ConnectionChecker(
            $this->client,
            $this->applicationState
        );
        $this->setUpLoggerMock($this->checker);
    }

    public function testCheckConnectionWhenNoWebsocketClientParametersProvider(): void
    {
        $this->websocketClientParametersProvider
            ->expects(self::exactly(2))
            ->method('getHost')
            ->willReturn('test.org');
        $this->checker->setWebsocketClientParametersProvider($this->websocketClientParametersProvider);
        $this->client->expects(self::once())
            ->method('connect');
        $this->client->expects(self::once())
            ->method('isConnected')
            ->willReturn(true);

        self::assertTrue($this->checker->checkConnection());

        // Checks that connection check result is cached
        self::assertTrue($this->checker->checkConnection());
    }

    public function testCheckConnection(): void
    {
        $this->websocketClientParametersProvider
            ->expects(self::exactly(2))
            ->method('getHost')
            ->willReturn('example.org');
        $this->client->expects(self::once())
            ->method('connect');
        $this->client->expects(self::once())
            ->method('isConnected')
            ->willReturn(true);

        $this->checker->setWebsocketClientParametersProvider($this->websocketClientParametersProvider);

        self::assertTrue($this->checker->checkConnection());

        // Checks that connection check result is cached
        self::assertTrue($this->checker->checkConnection());
    }

    public function testCheckConnectionWhenNotConfigured(): void
    {
        $this->websocketClientParametersProvider
            ->expects(self::once())
            ->method('getHost')
            ->willReturn('');
        $this->client->expects(self::never())
            ->method('connect');
        $this->client->expects(self::never())
            ->method('isConnected');

        $this->checker->setWebsocketClientParametersProvider($this->websocketClientParametersProvider);

        $this->checker->setWebsocketClientParametersProvider($this->websocketClientParametersProvider);

        self::assertFalse($this->checker->checkConnection());
    }

    public function testWsConnectedFail(): void
    {
        $this->websocketClientParametersProvider
            ->expects(self::exactly(2))
            ->method('getHost')
            ->willReturn('example.org');
        $this->client->expects(self::once())
            ->method('connect');
        $this->client->expects(self::once())
            ->method('isConnected')
            ->willReturn(false);

        $this->checker->setWebsocketClientParametersProvider($this->websocketClientParametersProvider);

        self::assertFalse($this->checker->checkConnection());

        // Checks that connection check result is cached
        self::assertFalse($this->checker->checkConnection());
    }

    public function testReset(): void
    {
        $this->websocketClientParametersProvider
            ->expects(self::exactly(3))
            ->method('getHost')
            ->willReturn('example.org');
        $this->client->expects(self::exactly(2))
            ->method('connect');
        $this->client->expects(self::exactly(2))
            ->method('isConnected')
            ->willReturn(false);

        $this->checker->setWebsocketClientParametersProvider($this->websocketClientParametersProvider);

        self::assertFalse($this->checker->checkConnection());

        // Checks that connection check result is cached
        self::assertFalse($this->checker->checkConnection());

        $this->checker->reset();

        self::assertFalse($this->checker->checkConnection());
    }

    public function testWsConnectedExceptionDuringInstallNoApplicationState(): void
    {
        $this->websocketClientParametersProvider
            ->expects(self::exactly(2))
            ->method('getHost')
            ->willReturn('example.org');
        $exception = new \Exception('sample message');
        $this->client->expects(self::once())
            ->method('connect')
            ->willThrowException($exception);
        $this->client->expects(self::never())
            ->method('isConnected');
        $this->loggerMock->expects(self::never())
            ->method(self::anything());

        $this->checker->setWebsocketClientParametersProvider($this->websocketClientParametersProvider);

        self::assertFalse($this->checker->checkConnection());

        // Checks that connection check result is cached
        self::assertFalse($this->checker->checkConnection());
    }

    public function testWsConnectedExceptionDuringInstall(): void
    {
        $this->websocketClientParametersProvider
            ->expects(self::exactly(2))
            ->method('getHost')
            ->willReturn('example.org');
        $exception = new \Exception('sample message');
        $this->client->expects(self::once())
            ->method('connect')
            ->willThrowException($exception);
        $this->client->expects(self::never())
            ->method('isConnected');
        $this->loggerMock->expects(self::never())
            ->method(self::anything());

        $this->applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturn(false);

        $this->checker->setWebsocketClientParametersProvider($this->websocketClientParametersProvider);

        self::assertFalse($this->checker->checkConnection());

        // Checks that connection check result is cached
        self::assertFalse($this->checker->checkConnection());
    }

    public function testWsConnectedException(): void
    {
        $this->websocketClientParametersProvider
            ->expects(self::exactly(2))
            ->method('getHost')
            ->willReturn('example.org');
        $exception = new \Exception('sample message');
        $this->client->expects(self::once())
            ->method('connect')
            ->willThrowException($exception);
        $this->client->expects(self::never())
            ->method('isConnected');
        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                'Failed to connect to websocket server: {message}',
                ['message' => $exception->getMessage(), 'e' => $exception]
            );

        $this->applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturn(true);

        $this->checker->setWebsocketClientParametersProvider($this->websocketClientParametersProvider);

        self::assertFalse($this->checker->checkConnection());

        // Checks that connection check result is cached
        self::assertFalse($this->checker->checkConnection());
    }

    public function testIsConfiguredWhenNoWebsocketClientParametersProvider(): void
    {
        $this->websocketClientParametersProvider
            ->expects(self::once())
            ->method('getHost')
            ->willReturn('test.org');
        $this->checker->setWebsocketClientParametersProvider($this->websocketClientParametersProvider);

        self::assertTrue($this->checker->isConfigured());
    }

    public function testIsConfiguredWhenHasHost(): void
    {
        $this->websocketClientParametersProvider
            ->expects(self::once())
            ->method('getHost')
            ->willReturn('example.org');

        $this->checker->setWebsocketClientParametersProvider($this->websocketClientParametersProvider);

        self::assertTrue($this->checker->isConfigured());
    }

    public function testIsConfiguredWhenNoHost(): void
    {
        $this->websocketClientParametersProvider
            ->expects(self::once())
            ->method('getHost')
            ->willReturn('');

        $this->checker->setWebsocketClientParametersProvider($this->websocketClientParametersProvider);

        self::assertFalse($this->checker->isConfigured());
    }
}
