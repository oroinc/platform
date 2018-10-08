<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\OriginSyncCredentials\NotificationSender;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\OriginSyncCredentials\NotificationSender\SocketNotificationSender;
use Oro\Bundle\SyncBundle\Client\ConnectionChecker;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;
use Oro\Bundle\UserBundle\Entity\User;

class SocketNotificationSenderTest extends \PHPUnit\Framework\TestCase
{
    /** @var SocketNotificationSender */
    private $sender;

    /**
     * @var WebsocketClientInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websocketClient;

    /**
     * @var ConnectionChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    private $connectionChecker;

    protected function setUp()
    {
        $this->websocketClient = $this->createMock(WebsocketClientInterface::class);
        $this->connectionChecker = $this->createMock(ConnectionChecker::class);

        $this->sender = new SocketNotificationSender($this->websocketClient, $this->connectionChecker);
    }

    public function testSendNotificationForSystemOrigin()
    {
        $origin = new UserEmailOrigin();
        $origin->setUser('test@example.com');
        $origin->setImapHost('example.com');

        $this->connectionChecker->expects($this->once())
            ->method('checkConnection')
            ->willReturn(true);

        $this->websocketClient->expects($this->once())
            ->method('publish')
            ->with(
                'oro/imap_sync_fail/*',
                ['username' => 'test@example.com', 'host' => 'example.com']
            );

        $this->sender->sendNotification($origin);
    }

    public function testSendNotification()
    {
        $origin = new UserEmailOrigin();
        $origin->setUser('test@example.com');
        $origin->setImapHost('example.com');

        $user = new User();
        $user->setId(456);
        $origin->setOwner($user);

        $this->connectionChecker->expects($this->once())
            ->method('checkConnection')
            ->willReturn(true);

        $this->websocketClient->expects($this->once())
            ->method('publish')
            ->with(
                'oro/imap_sync_fail/456',
                ['username' => 'test@example.com', 'host' => 'example.com']
            );

        $this->sender->sendNotification($origin);
    }

    public function testSendNotificationNoConnection()
    {
        $user = new User();
        $user->setId(456);

        $origin = new UserEmailOrigin();
        $origin->setUser('test@example.com');
        $origin->setImapHost('example.com');
        $origin->setOwner($user);

        $this->connectionChecker->expects($this->once())
            ->method('checkConnection')
            ->willReturn(false);

        $this->websocketClient->expects($this->never())
            ->method($this->anything());

        $this->sender->sendNotification($origin);
    }
}
