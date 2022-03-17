<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Client;

use Oro\Bundle\SyncBundle\Client\ConnectionChecker;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;

class ConnectionCheckerTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private WebsocketClientInterface|\PHPUnit\Framework\MockObject\MockObject $client;

    private ConnectionChecker $checker;

    protected function setUp(): void
    {
        $this->client = $this->createMock(WebsocketClientInterface::class);

        $this->checker = new ConnectionChecker($this->client);
        $this->setUpLoggerMock($this->checker);
    }

    public function testCheckConnection(): void
    {
        $this->client->expects($this->once())
            ->method('connect');
        $this->client->expects($this->once())
            ->method('isConnected')
            ->willReturn(true);

        $this->assertTrue($this->checker->checkConnection());
    }

    public function testWsConnectedFail(): void
    {
        $this->client->expects($this->once())
            ->method('connect');
        $this->client->expects($this->once())
            ->method('isConnected')
            ->willReturn(false);

        $this->assertFalse($this->checker->checkConnection());
    }

    public function testWsConnectedException(): void
    {
        $exception = new \Exception('sample message');
        $this->client->expects($this->once())
            ->method('connect')
            ->willThrowException($exception);
        $this->client->expects($this->never())
            ->method('isConnected');
        $this->loggerMock
            ->expects(self::once())
            ->method('warning')
            ->with(
                'Failed to connect to websocket server: {message}',
                ['message' => $exception->getMessage(), 'e' => $exception]
            );

        $this->assertFalse($this->checker->checkConnection());
    }
}
