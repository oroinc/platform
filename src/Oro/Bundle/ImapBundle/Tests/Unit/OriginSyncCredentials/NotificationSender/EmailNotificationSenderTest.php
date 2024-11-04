<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\OriginSyncCredentials\NotificationSender;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Sender\EmailTemplateSender;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\OriginSyncCredentials\NotificationSender\EmailNotificationSender;
use Oro\Bundle\NotificationBundle\Model\NotificationSettings;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmailNotificationSenderTest extends TestCase
{
    private EmailNotificationSender $sender;

    private EmailTemplateSender|MockObject $emailTemplateSender;

    private NotificationSettings|MockObject $notificationSettingsModel;

    #[\Override]
    protected function setUp(): void
    {
        $this->notificationSettingsModel = $this->createMock(NotificationSettings::class);
        $this->emailTemplateSender = $this->createMock(EmailTemplateSender::class);

        $this->sender = new EmailNotificationSender($this->notificationSettingsModel, $this->emailTemplateSender);
    }

    public function testSendNotificationForSystemOrigin(): void
    {
        $origin = new UserEmailOrigin();
        $origin->setUser('test@example.com');
        $origin->setImapHost('example.com');
        $mailbox = new Mailbox();
        $mailbox->setEmail('test@example.com');
        $origin->setMailbox($mailbox);

        $sender = From::emailAddress('sender@test.com', 'sender name');
        $this->notificationSettingsModel->expects(self::atLeastOnce())
            ->method('getSender')
            ->willReturn($sender);

        $this->emailTemplateSender->expects(self::once())
            ->method('sendEmailTemplate')
            ->with(
                $sender,
                $mailbox,
                'sync_wrong_credentials_system_box',
                [
                    'username' => 'test@example.com',
                    'host' => 'example.com',
                ]
            )
            ->willReturn($this->createMock(EmailUser::class));

        $this->sender->sendNotification($origin);
    }

    public function testSendNotification(): void
    {
        $origin = new UserEmailOrigin();
        $origin->setUser('test@example.com');
        $origin->setImapHost('example.com');

        $user = new User();
        $user->setEmail('user_email@test.com');
        $origin->setOwner($user);

        $sender = From::emailAddress('sender@test.com', 'sender name');
        $this->notificationSettingsModel->expects(self::atLeastOnce())
            ->method('getSender')
            ->willReturn($sender);

        $this->emailTemplateSender->expects(self::once())
            ->method('sendEmailTemplate')
            ->with(
                $sender,
                $user,
                'sync_wrong_credentials_user_box',
                ['host' => 'example.com', 'username' => 'test@example.com']
            )
            ->willReturn($this->createMock(EmailUser::class));

        $this->sender->sendNotification($origin);
    }
}
