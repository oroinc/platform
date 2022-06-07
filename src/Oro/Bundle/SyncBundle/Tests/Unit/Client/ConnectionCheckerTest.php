<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Client;

use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\SyncBundle\Client\ConnectionChecker;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;

class ConnectionCheckerTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private WebsocketClientInterface|\PHPUnit\Framework\MockObject\MockObject $client;

    private ApplicationState|\PHPUnit\Framework\MockObject\MockObject $applicationState;

    private ConnectionChecker $checker;

    protected function setUp(): void
    {
        $this->client = $this->createMock(WebsocketClientInterface::class);
        $this->applicationState = $this->createMock(ApplicationState::class);

        $this->checker = new ConnectionChecker($this->client, $this->applicationState);
        $this->setUpLoggerMock($this->checker);
    }

    public function testCheckConnection(): void
    {
        $this->client->expects(self::once())
            ->method('connect');
        $this->client->expects(self::once())
            ->method('isConnected')
            ->willReturn(true);

        self::assertTrue($this->checker->checkConnection());

        // Checks that connection check result is cached
        self::assertTrue($this->checker->checkConnection());
    }

    public function testWsConnectedFail(): void
    {
        $this->client->expects(self::once())
            ->method('connect');
        $this->client->expects(self::once())
            ->method('isConnected')
            ->willReturn(false);

        self::assertFalse($this->checker->checkConnection());

        // Checks that connection check result is cached
        self::assertFalse($this->checker->checkConnection());
    }

    public function testReset(): void
    {
        $this->client->expects(self::exactly(2))
            ->method('connect');
        $this->client->expects(self::exactly(2))
            ->method('isConnected')
            ->willReturn(false);

        self::assertFalse($this->checker->checkConnection());

        // Checks that connection check result is cached
        self::assertFalse($this->checker->checkConnection());

        $this->checker->reset();

        self::assertFalse($this->checker->checkConnection());
    }

    public function testWsConnectedExceptionDuringInstallNoApplicationState(): void
    {
        $exception = new \Exception('sample message');
        $this->client->expects(self::once())
            ->method('connect')
            ->willThrowException($exception);
        $this->client->expects(self::never())
            ->method('isConnected');
        $this->loggerMock->expects(self::never())
            ->method(self::anything());

        self::assertFalse($this->checker->checkConnection());

        // Checks that connection check result is cached
        self::assertFalse($this->checker->checkConnection());
    }

    public function testWsConnectedExceptionDuringInstall(): void
    {
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

        self::assertFalse($this->checker->checkConnection());

        // Checks that connection check result is cached
        self::assertFalse($this->checker->checkConnection());
    }

    public function testWsConnectedException(): void
    {
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

        self::assertFalse($this->checker->checkConnection());

        // Checks that connection check result is cached
        self::assertFalse($this->checker->checkConnection());
    }
}
