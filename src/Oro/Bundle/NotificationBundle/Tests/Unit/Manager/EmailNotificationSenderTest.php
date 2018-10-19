<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Model\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationSender;
use Oro\Bundle\NotificationBundle\Model\NotificationSettings;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotification;
use Oro\Bundle\NotificationBundle\Tests\Unit\Event\Handler\Stub\EmailHolderStub;

class EmailNotificationSenderTest extends \PHPUnit\Framework\TestCase
{
    use MessageQueueExtension;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|NotificationSettings
     */
    private $notificationSettings;

    /**
     * @var EmailNotificationSender
     */
    private $sender;

    protected function setUp()
    {
        $this->notificationSettings = $this->createMock(NotificationSettings::class);

        $this->sender = new EmailNotificationSender($this->notificationSettings, self::getMessageProducer());
    }

    public function testSendWithNotNotificationInterface()
    {
        $testSenderEmail = 'test_sender@email.com';
        $testSenderName = 'Test Name';
        $testSubject = 'test subject';
        $testBody = 'test body';
        $testReceiverEmail = new EmailHolderStub('test_receiver@email.com');
        $testContentType = 'text/html';

        $sender = From::emailAddress($testSenderEmail, $testSenderName);
        $this->notificationSettings
            ->expects($this->any())
            ->method('getSender')
            ->willReturn($sender);

        $notification = new TemplateEmailNotification(new EmailTemplateCriteria('template'), [$testReceiverEmail]);

        $emailTemplateModel = (new EmailTemplate())
            ->setSubject($testSubject)
            ->setContent($testBody)
            ->setType($testContentType);

        $this->sender->send($notification, $emailTemplateModel);

        self::assertMessageSent(
            \Oro\Bundle\NotificationBundle\Async\Topics::SEND_NOTIFICATION_EMAIL,
            [
                'sender'      => $sender->toArray(),
                'toEmail'     => $testReceiverEmail->getEmail(),
                'subject'     => $testSubject,
                'body'        => $testBody,
                'contentType' => $testContentType
            ]
        );
    }

    public function testSendWithNotificationInterfaceAndSenderEmailNotNull()
    {
        $testSenderEmail = 'test_sender@email.com';
        $testSenderName = 'Test Name';
        $testSubject = 'test subject';
        $testBody = 'test body';
        $testReceiverEmail = new EmailHolderStub('test_receiver@email.com');
        $testContentType = 'text/html';

        /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager $configManager */
        $this->notificationSettings
            ->expects($this->never())
            ->method('getSender');

        $sender = From::emailAddress($testSenderEmail, $testSenderName);
        $notification = new TemplateEmailNotification(
            new EmailTemplateCriteria('template'),
            [$testReceiverEmail],
            null,
            $sender
        );

        $emailTemplateModel = (new EmailTemplate())
            ->setSubject($testSubject)
            ->setContent($testBody)
            ->setType($testContentType);

        $this->sender->send($notification, $emailTemplateModel);

        self::assertMessageSent(
            \Oro\Bundle\NotificationBundle\Async\Topics::SEND_NOTIFICATION_EMAIL,
            [
                'sender'      => $sender->toArray(),
                'toEmail'     => $testReceiverEmail->getEmail(),
                'subject'     => $testSubject,
                'body'        => $testBody,
                'contentType' => 'text/html'
            ]
        );
    }

    public function testSendWithNotificationInterfaceAndSenderEmailIsNull()
    {
        $testSenderEmail = 'test_sender@email.com';
        $testSenderName = 'Test Name';
        $testSubject = 'test subject';
        $testBody = 'test body';
        $testReceiverEmail = new EmailHolderStub('test_receiver@email.com');
        $testContentType = 'text/html';

        $sender = From::emailAddress($testSenderEmail, $testSenderName);
        $this->notificationSettings
            ->expects($this->any())
            ->method('getSender')
            ->willReturn($sender);

        $notification = new TemplateEmailNotification(new EmailTemplateCriteria('template'), [$testReceiverEmail]);
        $emailTemplateModel = (new EmailTemplate())
            ->setSubject($testSubject)
            ->setContent($testBody)
            ->setType($testContentType);

        $this->sender->send($notification, $emailTemplateModel);

        self::assertMessageSent(
            \Oro\Bundle\NotificationBundle\Async\Topics::SEND_NOTIFICATION_EMAIL,
            [
                'sender'      => $sender->toArray(),
                'toEmail'     => $testReceiverEmail->getEmail(),
                'subject'     => $testSubject,
                'body'        => $testBody,
                'contentType' => 'text/html'
            ]
        );
    }

    public function testSendWithNotificationInterfaceAndWrongReceiverEmail()
    {
        $testSenderEmail = 'test_sender@email.com';
        $testSenderName = 'Test Name';
        $testSubject = 'test subject';
        $testBody = 'test body';
        $testReceiverEmail = new EmailHolderStub('test_receiver@email@com');
        $testContentType = 'text/html';

        $sender = From::emailAddress($testSenderEmail, $testSenderName);
        $this->notificationSettings
            ->expects($this->any())
            ->method('getSender')
            ->willReturn($sender);

        $notification = new TemplateEmailNotification(new EmailTemplateCriteria('template'), [$testReceiverEmail]);
        $emailTemplateModel = (new EmailTemplate())
            ->setSubject($testSubject)
            ->setContent($testBody)
            ->setType($testContentType);

        $this->sender->send($notification, $emailTemplateModel);

        self::assertMessagesCount(\Oro\Bundle\NotificationBundle\Async\Topics::SEND_NOTIFICATION_EMAIL, 0);
    }
}
