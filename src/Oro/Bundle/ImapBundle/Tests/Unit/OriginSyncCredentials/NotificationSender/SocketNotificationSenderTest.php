<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\OriginSyncCredentials\NotificationSender;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\OriginSyncCredentials\NotificationSender\SocketNotificationSender;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;
use Oro\Bundle\UserBundle\Entity\User;

class SocketNotificationSenderTest extends \PHPUnit_Framework_TestCase
{
    /** @var SocketNotificationSender */
    private $sender;

    /**
     * @var WebsocketClientInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $websocketClient;

    protected function setUp()
    {
        $this->websocketClient = $this->createMock(WebsocketClientInterface::class);

        $this->sender = new SocketNotificationSender($this->websocketClient);
    }

    public function testSendNotificationForSystemOrigin()
    {
        $origin = new UserEmailOrigin();
        $origin->setUser('test@example.com');
        $origin->setImapHost('example.com');

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

        $this->websocketClient->expects($this->once())
            ->method('publish')
            ->with(
                'oro/imap_sync_fail/456',
                ['username' => 'test@example.com', 'host' => 'example.com']
            );

        $this->sender->sendNotification($origin);
    }
}
