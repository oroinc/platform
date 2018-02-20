<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\OriginSyncCredentials\NotificationSender;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\OriginSyncCredentials\NotificationSender\EmailNotificationSender;
use Oro\Bundle\UserBundle\Entity\User;

class EmailNotificationSenderTest extends \PHPUnit_Framework_TestCase
{
    /** @var EmailNotificationSender */
    private $sender;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $emailRenderer;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $doctrine;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $mailer;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->emailRenderer = $this->createMock(EmailRenderer::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->mailer = $this->createMock(\Swift_Mailer::class);

        $this->sender = new EmailNotificationSender(
            $this->configManager,
            $this->emailRenderer,
            $this->doctrine,
            $this->mailer
        );
    }

    public function testSendNotificationForSystemOrigin()
    {
        $origin = new UserEmailOrigin();
        $origin->setUser('test@example.com');
        $origin->setImapHost('example.com');
        $mailbox = new Mailbox();
        $mailbox->setEmail('test@example.com');
        $origin->setMailbox($mailbox);

        $template = new EmailTemplate();

        $em = $this->createMock(EntityManager::class);
        $repository = $this->createMock(EntityRepository::class);

        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap(
                [
                    ['oro_notification.email_notification_sender_email', false, false, null, 'sender@test.com'],
                    ['oro_notification.email_notification_sender_name', false, false, null, 'sender name']
                ]
            );

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(EmailTemplate::class)
            ->willReturn($em);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(EmailTemplate::class)
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => 'sync_wrong_credentials_system_box'])
            ->willReturn($template);

        $this->emailRenderer->expects($this->once())
            ->method('compileMessage')
            ->with(
                $template,
                [
                    'username' => 'test@example.com',
                    'host' => 'example.com'
                ]
            )
            ->willReturn(['subject', 'emailData']);

        $this->mailer->expects($this->once())
            ->method('send')
            ->willReturnCallback(function ($message) {
                /** @var $message \Swift_Message */
                $this->assertEquals('subject', $message->getSubject());
                $this->assertEquals(['sender@test.com' => 'sender name'], $message->getFrom());
                $this->assertEquals(['test@example.com' => null], $message->getTo());
                $this->assertEquals('emailData', $message->getBody());

                return 1;
            });

        $this->sender->sendNotification($origin);
    }

    public function testSendNotification()
    {
        $origin = new UserEmailOrigin();
        $origin->setUser('test@example.com');
        $origin->setImapHost('example.com');

        $user = new User();
        $user->setEmail('user_email@test.com');
        $origin->setOwner($user);

        $template = new EmailTemplate();

        $em = $this->createMock(EntityManager::class);
        $repository = $this->createMock(EntityRepository::class);

        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap(
                [
                    ['oro_notification.email_notification_sender_email', false, false, null, 'sender@test.com'],
                    ['oro_notification.email_notification_sender_name', false, false, null, 'sender name']
                ]
            );

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(EmailTemplate::class)
            ->willReturn($em);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(EmailTemplate::class)
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => 'sync_wrong_credentials_user_box'])
            ->willReturn($template);

        $this->emailRenderer->expects($this->once())
            ->method('compileMessage')
            ->with(
                $template,
                [
                    'username' => 'test@example.com',
                    'host' => 'example.com'
                ]
            )
            ->willReturn(['subject', 'emailData']);

        $this->mailer->expects($this->once())
            ->method('send')
            ->willReturnCallback(function ($message) {
                /** @var $message \Swift_Message */
                $this->assertEquals('subject', $message->getSubject());
                $this->assertEquals(['sender@test.com' => 'sender name'], $message->getFrom());
                $this->assertEquals(['user_email@test.com' => null], $message->getTo());
                $this->assertEquals('emailData', $message->getBody());

                return 1;
            });

        $this->sender->sendNotification($origin);
    }
}
