<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\OriginSyncCredentials\NotificationSender;

use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Manager\EmailTemplateManager;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\OriginSyncCredentials\NotificationSender\EmailNotificationSender;
use Oro\Bundle\NotificationBundle\Model\NotificationSettings;
use Oro\Bundle\UserBundle\Entity\User;

class EmailNotificationSenderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailNotificationSender */
    private $sender;

    /** @var EmailTemplateManager|\PHPUnit\Framework\MockObject\MockObject */
    private $emailTemplateManager;

    /** @var NotificationSettings|\PHPUnit\Framework\MockObject\MockObject */
    private $notificationSettingsModel;

    protected function setUp(): void
    {
        $this->notificationSettingsModel = $this->createMock(NotificationSettings::class);
        $this->emailTemplateManager = $this->createMock(EmailTemplateManager::class);

        $this->sender = new EmailNotificationSender($this->notificationSettingsModel, $this->emailTemplateManager);
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
        $this->notificationSettingsModel->expects($this->atLeastOnce())
            ->method('getSender')
            ->willReturn($sender);

        $this->emailTemplateManager->expects($this->once())
            ->method('sendTemplateEmail')
            ->with(
                $sender,
                [$mailbox],
                new EmailTemplateCriteria('sync_wrong_credentials_system_box'),
                [
                    'username' => 'test@example.com',
                    'host' => 'example.com'
                ]
            );

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
        $this->notificationSettingsModel->expects($this->atLeastOnce())
            ->method('getSender')
            ->willReturn($sender);

        $this->emailTemplateManager->expects($this->once())
            ->method('sendTemplateEmail')
            ->with(
                $sender,
                [$user],
                new EmailTemplateCriteria('sync_wrong_credentials_user_box'),
                ['host' => 'example.com', 'username' => 'test@example.com']
            )
            ->willReturn(1);

        $this->sender->sendNotification($origin);
    }
}
