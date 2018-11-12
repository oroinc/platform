<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Monolog;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LoggerBundle\Mailer\MessageFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MessageFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateMessage()
    {
        $messageFactory = new MessageFactory();
        $message = new \Swift_Message();

        $mailer = $this->getMockBuilder(\Swift_Mailer::class)->disableOriginalConstructor()->getMock();
        $mailer->expects($this->once())
            ->method('createMessage')
            ->willReturn($message);

        $config = $this->getMockBuilder(ConfigManager::class)->disableOriginalConstructor()->getMock();
        $config->expects($this->exactly(3))
            ->method('get')
            ->willReturnMap(
                [
                    [
                        'oro_logger.email_notification_recipients',
                        false,
                        false,
                        null,
                        'recipient1@example.com;recipient2@example.com'
                    ],
                    ['oro_notification.email_notification_sender_email', false, false, null, 'sender@example.com'],
                    ['oro_logger.email_notification_subject', false, false, null, 'Subject'],
                ]
            );

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap(
                [
                    ['swiftmailer.mailer.default', 1, $mailer],
                    ['oro_config.global', 1, $config],
                ]
            );
        $container->expects($this->once())
            ->method('has')
            ->with('oro_config.global')
            ->willReturn(true);

        $messageFactory->setContainer($container);
        $messageFactory->createMessage('text', []);

        $this->assertEquals('Subject', $message->getSubject());
        $this->assertEquals(['recipient1@example.com' => null, 'recipient2@example.com' => null], $message->getTo());
        $this->assertEquals(['sender@example.com' => null], $message->getFrom());
    }

    public function testCreateMessageWithoutConfig()
    {
        $messageFactory = new MessageFactory();
        $message = new \Swift_Message();

        $mailer = $this->getMockBuilder(\Swift_Mailer::class)->disableOriginalConstructor()->getMock();
        $mailer->expects($this->once())
            ->method('createMessage')
            ->willReturn($message);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with('swiftmailer.mailer.default')
            ->willReturn($mailer);

        $container->expects($this->once())
            ->method('has')
            ->with('oro_config.global')
            ->willReturn(false);


        $messageFactory->setContainer($container);
        $messageFactory->createMessage('text', []);
    }
}
