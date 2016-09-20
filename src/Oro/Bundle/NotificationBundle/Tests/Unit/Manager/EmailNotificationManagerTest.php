<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\NotificationBundle\Manager\AbstractNotificationManager;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\NotificationBundle\Model\EmailNotificationInterface;
use Oro\Bundle\NotificationBundle\Model\SenderAwareEmailNotificationInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Psr\Log\LoggerInterface;

class EmailNotificationManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldCreateWithAllRequiredArguments()
    {
        new EmailNotificationManager(
            $this->createEmailRendererMock(),
            $this->createConfigManagerMock(),
            $this->createMessageProducerMock(),
            $this->createLoggerMock()
        );
    }

    public function testShouldExtendAbstractNotificationManager()
    {
        $manager = new EmailNotificationManager(
            $this->createEmailRendererMock(),
            $this->createConfigManagerMock(),
            $this->createMessageProducerMock(),
            $this->createLoggerMock()
        );

        $this->assertInstanceOf(AbstractNotificationManager::class, $manager);
    }


    public function testSendMessageTakeSenderFromConfigManagerIfNotificationImplementsEmailNotificationInterface()
    {
        $testSenderEmail = 'test_sender@email.com';
        $testSenderName = 'Test Name';
        $testSubject = 'test subject';
        $testBody = 'test body';
        $testReceiverEmail = 'test_receiver@email.com';

        $configManager = $this->createConfigManagerMock();
        $configManager
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['oro_notification.email_notification_sender_email', false, false, null, $testSenderEmail],
                ['oro_notification.email_notification_sender_name', false, false, null, $testSenderName ]
            ])
        ;

        $template = $this->createTemplateMock();
        $template->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('html'))
        ;

        $object = $this->createUserMock();

        $emailRenderer = $this->createEmailRendererMock();
        $emailRenderer->expects($this->once())
            ->method('compileMessage')
            ->with($template, ['entity' => $object])
            ->will($this->returnValue([$testSubject, $testBody]))
        ;

        $notification = $this->createEmailNotificationMock();
        $notification->expects($this->once())
            ->method('getTemplate')
            ->will($this->returnValue($template))
        ;

        $notification->expects($this->once())
            ->method('getRecipientEmails')
            ->will($this->returnValue([$testReceiverEmail]))
        ;

        $notification->expects($this->never())
            ->method('getSenderEmail')
        ;

        $notification->expects($this->never())
            ->method('getSenderName')
        ;

        $messageProducer = $this->createMessageProducerMock();
        $messageProducer
            ->expects($this->once())
            ->method('send')
            ->with(EmailNotificationManager::TOPIC, [
                'from' => [
                    'email' => $testSenderEmail,
                    'name' => $testSenderName,
                ],
                'to' => $testReceiverEmail,
                'subject' => $testSubject,
                'body' => [
                    'body' => $testBody,
                    'contentType' => 'text/html'
                ]
            ])
        ;

        $manager = new EmailNotificationManager(
            $emailRenderer,
            $configManager,
            $messageProducer,
            $this->createLoggerMock()
        );

        $manager->process($object, [$notification]);
    }

    public function testUseSenderFromCMIfSendingAwareEmailNotificationInterfaceAndNotificationSenderEmailNotNull()
    {
        $testSenderEmail = 'test_sender@email.com';
        $testSenderName = 'Test Name';
        $testSubject = 'test subject';
        $testBody = 'test body';
        $testReceiverEmail = 'test_receiver@email.com';

        $configManager = $this->createConfigManagerMock();
        $configManager
            ->expects($this->never())
            ->method('get')
        ;

        $template = $this->createTemplateMock();
        $template->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('html'))
        ;

        $object = $this->createUserMock();

        $emailRenderer = $this->createEmailRendererMock();
        $emailRenderer->expects($this->once())
            ->method('compileMessage')
            ->with($template, ['entity' => $object])
            ->will($this->returnValue([$testSubject, $testBody]))
        ;

        $notification = $this->createSenderAwareEmailNotificationMock();
        $notification->expects($this->once())
            ->method('getTemplate')
            ->will($this->returnValue($template))
        ;

        $notification->expects($this->once())
            ->method('getRecipientEmails')
            ->will($this->returnValue([$testReceiverEmail]))
        ;

        $notification->expects($this->exactly(2))
            ->method('getSenderEmail')
            ->willReturn($testSenderEmail)
        ;

        $notification->expects($this->once())
            ->method('getSenderName')
            ->willReturn($testSenderName)
        ;

        $messageProducer = $this->createMessageProducerMock();
        $messageProducer
            ->expects($this->once())
            ->method('send')
            ->with(EmailNotificationManager::TOPIC, [
                'from' => [
                    'email' => $testSenderEmail,
                    'name' => $testSenderName,
                ],
                'to' => $testReceiverEmail,
                'subject' => $testSubject,
                'body' => [
                    'body' => $testBody,
                    'contentType' => 'text/html'
                ]
            ])
        ;

        $manager = new EmailNotificationManager(
            $emailRenderer,
            $configManager,
            $messageProducer,
            $this->createLoggerMock()
        );

        $manager->process($object, [$notification]);
    }

    public function testUseSenderFromCMIfSendingAwareEmailNotificationInterfaceButNotificationSenderEmailIsNull()
    {
        $testSenderEmail = 'test_sender@email.com';
        $testSenderName = 'Test Name';
        $testSubject = 'test subject';
        $testBody = 'test body';
        $testReceiverEmail = 'test_receiver@email.com';

        $configManager = $this->createConfigManagerMock();
        $configManager
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['oro_notification.email_notification_sender_email', false, false, null, $testSenderEmail],
                ['oro_notification.email_notification_sender_name', false, false, null, $testSenderName ]
            ])
        ;

        $template = $this->createTemplateMock();
        $template->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('html'))
        ;

        $object = $this->createUserMock();

        $emailRenderer = $this->createEmailRendererMock();
        $emailRenderer->expects($this->once())
            ->method('compileMessage')
            ->with($template, ['entity' => $object])
            ->will($this->returnValue([$testSubject, $testBody]))
        ;

        $notification = $this->createSenderAwareEmailNotificationMock();
        $notification->expects($this->once())
            ->method('getTemplate')
            ->will($this->returnValue($template))
        ;

        $notification->expects($this->once())
            ->method('getRecipientEmails')
            ->will($this->returnValue([$testReceiverEmail]))
        ;

        $notification->expects($this->once())
            ->method('getSenderEmail')
            ->willReturn(null)
        ;

        $notification->expects($this->never())
            ->method('getSenderName')
        ;

        $messageProducer = $this->createMessageProducerMock();
        $messageProducer
            ->expects($this->once())
            ->method('send')
            ->with(EmailNotificationManager::TOPIC, [
                'from' => [
                    'email' => $testSenderEmail,
                    'name' => $testSenderName,
                ],
                'to' => $testReceiverEmail,
                'subject' => $testSubject,
                'body' => [
                    'body' => $testBody,
                    'contentType' => 'text/html'
                ]
            ])
        ;

        $manager = new EmailNotificationManager(
            $emailRenderer,
            $configManager,
            $messageProducer,
            $this->createLoggerMock()
        );

        $manager->process($object, [$notification]);
    }

    public function testShouldNotSendMessageIfTwigExceptionAppear()
    {
        $configManager = $this->createConfigManagerMock();
        $configManager
            ->expects($this->never())
            ->method('get')
        ;

        $template = $this->createTemplateMock();
        $template->expects($this->never())
            ->method('getType')
        ;

        $object = $this->createUserMock();

        $emailRenderer = $this->createEmailRendererMock();
        $emailRenderer->expects($this->once())
            ->method('compileMessage')
            ->will($this->throwException(new \Twig_Error('An error occured')))
        ;

        $notification = $this->createSenderAwareEmailNotificationMock();
        $notification->expects($this->once())
            ->method('getTemplate')
            ->will($this->returnValue($template))
        ;

        $notification->expects($this->never())
            ->method('getRecipientEmails')
        ;

        $notification->expects($this->never())
            ->method('getSenderEmail')
        ;

        $notification->expects($this->never())
            ->method('getSenderName')
        ;

        $messageProducer = $this->createMessageProducerMock();
        $messageProducer
            ->expects($this->never())
            ->method('send')
        ;

        $logger = $this->createLoggerMock();
        $logger->expects($this->once())
            ->method('error')
        ;

        $manager = new EmailNotificationManager(
            $emailRenderer,
            $configManager,
            $messageProducer,
            $logger
        );

        $manager->process($object, [$notification]);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | EmailRenderer
     */
    private function createEmailRendererMock()
    {
        return $this
            ->getMockBuilder(EmailRenderer::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | ConfigManager
     */
    private function createConfigManagerMock()
    {
        return $this
            ->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | MessageProducerInterface
     */
    private function createMessageProducerMock()
    {
        return $this->getMock(MessageProducerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->getMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | EmailTemplate
     */
    private function createTemplateMock()
    {
        return $this->getMock(EmailTemplate::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | EmailNotificationInterface
     */
    private function createEmailNotificationMock()
    {
        return $this->getMock(EmailNotificationInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | SenderAwareEmailNotificationInterface
     */
    private function createSenderAwareEmailNotificationMock()
    {
        return $this->getMock(SenderAwareEmailNotificationInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject | User
     */
    private function createUserMock()
    {
        return $this->getMock(User::class);
    }
}
