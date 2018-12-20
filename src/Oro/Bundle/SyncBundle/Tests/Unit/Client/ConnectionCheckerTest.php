<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Client;

use Gos\Component\WebSocketClient\Exception\BadResponseException;
use Oro\Bundle\SyncBundle\Client\ConnectionChecker;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;

class ConnectionCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var WebsocketClientInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $client;

    /** @var ConnectionChecker */
    protected $checker;

    public function setUp()
    {
        $this->client = $this->createMock(WebsocketClientInterface::class);
        $this->checker = new ConnectionChecker($this->client);
    }

    public function testCheckConnection()
    {
        $this->client->expects($this->once())->method('connect');
        $this->client->expects($this->once())
            ->method('isConnected')
            ->willReturn(true);

        $this->assertTrue($this->checker->checkConnection());
    }

    public function testWsConnectedFail()
    {
        $this->client->expects($this->once())->method('connect');
        $this->client->expects($this->once())
            ->method('isConnected')
            ->willReturn(false);

        $this->assertFalse($this->checker->checkConnection());
    }

    public function testWsConnectedException()
    {
        $this->client->expects($this->once())
            ->method('connect')
            ->willThrowException(new BadResponseException());
        $this->client->expects($this->never())
            ->method('isConnected');

        $this->assertFalse($this->checker->checkConnection());
    }
}
