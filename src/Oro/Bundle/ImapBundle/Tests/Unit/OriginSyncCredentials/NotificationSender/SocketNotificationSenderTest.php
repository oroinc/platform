<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\OriginSyncCredentials\NotificationSender;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\OriginSyncCredentials\NotificationSender\SocketNotificationSender;
use Oro\Bundle\SyncBundle\Wamp\TopicPublisher;
use Oro\Bundle\UserBundle\Entity\User;

class SocketNotificationSenderTest extends \PHPUnit_Framework_TestCase
{
    /** @var SocketNotificationSender */
    private $sender;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $topicPublisher;

    protected function setUp()
    {
        $this->topicPublisher = $this->createMock(TopicPublisher::class);

        $this->sender = new SocketNotificationSender($this->topicPublisher);
    }

    public function testSendNotificationForSystemOrigin()
    {
        $origin = new UserEmailOrigin();
        $origin->setUser('test@example.com');
        $origin->setImapHost('example.com');

        $this->topicPublisher->expects($this->once())
            ->method('send')
            ->with(
                'oro/imap_sync_fail_system',
                [
                    'username' => 'test@example.com',
                    'host' => 'example.com'
                ]
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

        $this->topicPublisher->expects($this->once())
            ->method('send')
            ->with(
                'oro/imap_sync_fail_u_456',
                [
                    'username' => 'test@example.com',
                    'host' => 'example.com'
                ]
            );

        $this->sender->sendNotification($origin);
    }
}
