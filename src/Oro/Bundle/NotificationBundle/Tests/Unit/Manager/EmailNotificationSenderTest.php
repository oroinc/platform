<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationSender;
use Oro\Bundle\NotificationBundle\Model\EmailNotificationInterface;
use Oro\Bundle\NotificationBundle\Model\SenderAwareEmailNotificationInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class EmailNotificationSenderTest extends \PHPUnit_Framework_TestCase
{
    use MessageQueueExtension;

    public function testShouldCreateWithAllRequiredArguments()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager $configManager */
        $configManager = $this->createMock(ConfigManager::class);

        /** @var \PHPUnit_Framework_MockObject_MockObject|MessageProducerInterface $producer */
        $producer = $this->createMock(MessageProducerInterface::class);

        new EmailNotificationSender(
            $configManager,
            $producer
        );
    }

    public function testSendWithNotNotificationInterface()
    {
        $testSenderEmail = 'test_sender@email.com';
        $testSenderName = 'Test Name';
        $testSubject = 'test subject';
        $testBody = 'test body';
        $testReceiverEmail = 'test_receiver@email.com';
        $testContentType = 'text/html';

        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager $configManager */
        $configManager = $this->createMock(ConfigManager::class);
        $configManager
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['oro_notification.email_notification_sender_email', false, false, null, $testSenderEmail],
                ['oro_notification.email_notification_sender_name', false, false, null, $testSenderName]
            ]);

        /** @var \PHPUnit_Framework_MockObject_MockObject|EmailNotificationInterface $notification */
        $notification = $this->createMock(EmailNotificationInterface::class);
        $notification->expects($this->once())
            ->method('getRecipientEmails')
            ->willReturn([$testReceiverEmail]);

        $sender = new EmailNotificationSender(
            $configManager,
            self::getMessageProducer()
        );
        $sender->send($notification, $testSubject, $testBody, $testContentType);

        self::assertMessageSent(
            \Oro\Bundle\NotificationBundle\Async\Topics::SEND_NOTIFICATION_EMAIL,
            [
                'fromEmail'   => $testSenderEmail,
                'fromName'    => $testSenderName,
                'toEmail'     => $testReceiverEmail,
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
        $testReceiverEmail = 'test_receiver@email.com';
        $testContentType = 'text/html';

        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager $configManager */
        $configManager = $this->createMock(ConfigManager::class);
        $configManager
            ->expects($this->never())
            ->method('get');

        /** @var \PHPUnit_Framework_MockObject_MockObject|SenderAwareEmailNotificationInterface $notification */
        $notification = $this->createMock(SenderAwareEmailNotificationInterface::class);
        $notification->expects($this->once())
            ->method('getRecipientEmails')
            ->willReturn([$testReceiverEmail]);
        $notification->expects($this->exactly(2))
            ->method('getSenderEmail')
            ->willReturn($testSenderEmail);
        $notification->expects($this->once())
            ->method('getSenderName')
            ->willReturn($testSenderName);

        $sender = new EmailNotificationSender(
            $configManager,
            self::getMessageProducer()
        );
        $sender->send($notification, $testSubject, $testBody, $testContentType);

        self::assertMessageSent(
            \Oro\Bundle\NotificationBundle\Async\Topics::SEND_NOTIFICATION_EMAIL,
            [
                'fromEmail'   => $testSenderEmail,
                'fromName'    => $testSenderName,
                'toEmail'     => $testReceiverEmail,
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
        $testReceiverEmail = 'test_receiver@email.com';
        $testContentType = 'text/html';

        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager $configManager */
        $configManager = $this->createMock(ConfigManager::class);
        $configManager
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['oro_notification.email_notification_sender_email', false, false, null, $testSenderEmail],
                ['oro_notification.email_notification_sender_name', false, false, null, $testSenderName]
            ]);

        /** @var \PHPUnit_Framework_MockObject_MockObject|SenderAwareEmailNotificationInterface $notification */
        $notification = $this->createMock(SenderAwareEmailNotificationInterface::class);
        $notification->expects($this->once())
            ->method('getRecipientEmails')
            ->will($this->returnValue([$testReceiverEmail]));
        $notification->expects($this->once())
            ->method('getSenderEmail')
            ->willReturn(null);
        $notification->expects($this->never())
            ->method('getSenderName');

        $sender = new EmailNotificationSender(
            $configManager,
            self::getMessageProducer()
        );
        $sender->send($notification, $testSubject, $testBody, $testContentType);

        self::assertMessageSent(
            \Oro\Bundle\NotificationBundle\Async\Topics::SEND_NOTIFICATION_EMAIL,
            [
                'fromEmail'   => $testSenderEmail,
                'fromName'    => $testSenderName,
                'toEmail'     => $testReceiverEmail,
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
        $testReceiverEmail = 'test_receiver@email@com';
        $testContentType = 'text/html';

        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager $configManager */
        $configManager = $this->createMock(ConfigManager::class);
        $configManager
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['oro_notification.email_notification_sender_email', false, false, null, $testSenderEmail],
                ['oro_notification.email_notification_sender_name', false, false, null, $testSenderName]
            ]);

        /** @var \PHPUnit_Framework_MockObject_MockObject|SenderAwareEmailNotificationInterface $notification */
        $notification = $this->createMock(SenderAwareEmailNotificationInterface::class);
        $notification->expects($this->once())
            ->method('getRecipientEmails')
            ->will($this->returnValue([$testReceiverEmail]));
        $notification->expects($this->once())
            ->method('getSenderEmail')
            ->willReturn(null);
        $notification->expects($this->never())
            ->method('getSenderName');

        $sender = new EmailNotificationSender(
            $configManager,
            self::getMessageProducer()
        );
        $sender->send($notification, $testSubject, $testBody, $testContentType);

        self::assertMessagesCount(\Oro\Bundle\NotificationBundle\Async\Topics::SEND_NOTIFICATION_EMAIL, 0);
    }
}
