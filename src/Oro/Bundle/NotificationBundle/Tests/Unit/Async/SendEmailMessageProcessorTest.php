<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Async\TemplateEmailMessageSender;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Mailer\DirectMailer;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\NotificationBundle\Async\SendEmailMessageProcessor;
use Oro\Bundle\NotificationBundle\Async\Topics;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SendEmailMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    private const USER_ID = 24;

    public function testShouldConstructWithRequiredArguments()
    {
        new SendEmailMessageProcessor(
            $this->createMock(DirectMailer::class),
            $this->createMock(Processor::class),
            $this->createMock(ManagerRegistry::class),
            $this->createMock(EmailRenderer::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(TemplateEmailMessageSender::class)
        );
    }

    public function testShouldBeSubscribedForTopics()
    {
        $expectedSubscribedTopics = [
            Topics::SEND_NOTIFICATION_EMAIL,
        ];

        self::assertEquals($expectedSubscribedTopics, SendEmailMessageProcessor::getSubscribedTopics());
    }

    public function testShouldRejectIfBodyEmpty()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid message');

        $processor = new SendEmailMessageProcessor(
            $this->createMock(DirectMailer::class),
            $this->createMock(Processor::class),
            $this->createMock(ManagerRegistry::class),
            $this->createMock(EmailRenderer::class),
            $logger,
            $this->createMock(TemplateEmailMessageSender::class)
        );

        $sender = From::emailAddress('from@email.com');
        $message = new Message();
        $message->setBody(JSON::encode([
            'toEmail' => 'to@email.com',
            'sender'  => $sender->toArray(),
        ]));

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testShouldRejectIfSenderNotSet()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid message');

        $processor = new SendEmailMessageProcessor(
            $this->createMock(DirectMailer::class),
            $this->createMock(Processor::class),
            $this->createMock(ManagerRegistry::class),
            $this->createMock(EmailRenderer::class),
            $logger,
            $this->createMock(TemplateEmailMessageSender::class)
        );

        $message = new Message();
        $message->setBody(JSON::encode([
            'body'    => 'body',
            'toEmail' => 'to@email.com',
        ]));

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testShouldRejectIfRecepientNotSet()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid message');

        $processor = new SendEmailMessageProcessor(
            $this->createMock(DirectMailer::class),
            $this->createMock(Processor::class),
            $this->createMock(ManagerRegistry::class),
            $this->createMock(EmailRenderer::class),
            $logger,
            $this->createMock(TemplateEmailMessageSender::class)
        );

        $sender = From::emailAddress('from@email.com');
        $message = new Message();
        $message->setBody(JSON::encode([
            'body'   => 'body',
            'sender' => $sender->toArray(),
        ]));

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testShouldRejectIfTemplatePassedButBodyIsNotArray()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid message');

        $processor = new SendEmailMessageProcessor(
            $this->createMock(DirectMailer::class),
            $this->createMock(Processor::class),
            $this->createMock(ManagerRegistry::class),
            $this->createMock(EmailRenderer::class),
            $logger,
            $this->createMock(TemplateEmailMessageSender::class)
        );

        $message = new Message();
        $sender = From::emailAddress('from@email.com');
        $message->setBody(JSON::encode([
            'sender'   => $sender->toArray(),
            'toEmail'  => 'to@email.com',
            'template' => 'template_name',
        ]));

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testShouldRejectIfSendingFailed()
    {
        $mailer = $this->createMock(DirectMailer::class);
        $mailer->expects($this->once())
            ->method('send')
            ->willReturn(0);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('Cannot send message');

        $processor = new SendEmailMessageProcessor(
            $mailer,
            $this->createMock(Processor::class),
            $this->createMock(ManagerRegistry::class),
            $this->createMock(EmailRenderer::class),
            $logger,
            $this->createMock(TemplateEmailMessageSender::class)
        );

        $message = new Message();
        $sender = From::emailAddress('from@email.com');
        $message->setBody(JSON::encode([
            'body'    => 'Message body',
            'toEmail' => 'to@email.com',
            'sender'  => $sender->toArray(),
        ]));

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testShouldSendEmailAndReturnAckIfAllParametersCorrect()
    {
        $mailer = $this->createMock(DirectMailer::class);
        $mailer->expects($this->once())
            ->method('send')
            ->willReturn(1);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())
            ->method('critical');

        $processor = new SendEmailMessageProcessor(
            $mailer,
            $this->createMock(Processor::class),
            $this->createMock(ManagerRegistry::class),
            $this->createMock(EmailRenderer::class),
            $logger,
            $this->createMock(TemplateEmailMessageSender::class)
        );

        $message = new Message();
        $sender = From::emailAddress('from@email.com');
        $message->setBody(JSON::encode([
            'body'    => 'Message body',
            'toEmail' => 'to@email.com',
            'sender'  => $sender->toArray(),
        ]));

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testShouldRenderCorrectEmailTemplate()
    {
        $emailTemplate = $this->createMock(EmailTemplate::class);

        $repository = $this->createMock(EmailTemplateRepository::class);
        $repository->expects($this->once())
            ->method('findByName')
            ->with('template_name')
            ->willReturn($emailTemplate);

        $manager = $this->createMock(EntityManager::class);
        $manager->expects($this->once())
            ->method('getRepository')
            ->with(EmailTemplate::class)
            ->willReturn($repository);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with(EmailTemplate::class)
            ->willReturn($manager);

        $emailRenderer = $this->createMock(EmailRenderer::class);
        $emailRenderer->expects($this->once())
            ->method('compileMessage')
            ->with($this->isInstanceOf(EmailTemplate::class), ['body_parameter' => 'value'])
            ->willReturn(['email subject', 'email body']);

        $processor = new SendEmailMessageProcessor(
            $this->createMock(DirectMailer::class),
            $this->createMock(Processor::class),
            $managerRegistry,
            $emailRenderer,
            $this->createMock(LoggerInterface::class),
            $this->createMock(TemplateEmailMessageSender::class)
        );

        $message = new Message();
        $sender = From::emailAddress('from@email.com');
        $message->setBody(JSON::encode([
            'toEmail'  => 'to@email.com',
            'sender'   => $sender->toArray(),
            'template' => 'template_name',
            'body'     => ['body_parameter' => 'value'],
        ]));

        $processor->process($message, $this->createMock(SessionInterface::class));
    }

    public function testShouldThrowExceptionIfTemplateNotFound()
    {
        $this->expectException(\RuntimeException::class);
        $repository = $this->createMock(EmailTemplateRepository::class);
        $repository->expects($this->once())
            ->method('findByName')
            ->with('template_name')
            ->willReturn(null);

        $manager = $this->createMock(EntityManager::class);
        $manager->expects($this->once())
            ->method('getRepository')
            ->with(EmailTemplate::class)
            ->willReturn($repository);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with(EmailTemplate::class)
            ->willReturn($manager);

        $emailRenderer = $this->createMock(EmailRenderer::class);
        $emailRenderer->expects($this->never())
            ->method('compileMessage');

        $processor = new SendEmailMessageProcessor(
            $this->createMock(DirectMailer::class),
            $this->createMock(Processor::class),
            $managerRegistry,
            $emailRenderer,
            $this->createMock(LoggerInterface::class),
            $this->createMock(TemplateEmailMessageSender::class)
        );

        $message = new Message();
        $sender = From::emailAddress('from@email.com');
        $message->setBody(JSON::encode([
            'toEmail'  => 'to@email.com',
            'sender'   => $sender->toArray(),
            'template' => 'template_name',
            'body'     => ['body_parameter' => 'value'],
        ]));

        $processor->process($message, $this->createMock(SessionInterface::class));
    }

    public function testProcessWhenMessageIsTranslatableAndMessageSent()
    {
        $templateEmailMessageSender = $this->createMock(TemplateEmailMessageSender::class);
        $processor = new SendEmailMessageProcessor(
            $this->createMock(DirectMailer::class),
            $this->createMock(Processor::class),
            $this->createMock(ManagerRegistry::class),
            $this->createMock(EmailRenderer::class),
            $this->createMock(LoggerInterface::class),
            $templateEmailMessageSender
        );

        $sender = From::emailAddress('from@email.com');
        $messageBody = [
            'toEmail'  => 'to@email.com',
            'sender'   => $sender->toArray(),
            'template' => 'template_name',
            'body'     => ['body_parameter' => 'value'],
            'userId'   => self::USER_ID,
        ];

        $message = new Message();
        $message->setBody(JSON::encode($messageBody));

        $templateEmailMessageSender->expects($this->any())
            ->method('isTranslatable')
            ->with(
                $this->callback(function ($message) use ($messageBody) {
                    return $message == array_replace_recursive($message, $messageBody);
                })
            )
            ->willReturn(true);

        $templateEmailMessageSender->expects($this->any())
            ->method('sendTranslatedMessage')
            ->with(
                $this->callback(function ($message) use ($messageBody) {
                    return $message == array_replace_recursive($message, $messageBody);
                })
            )
            ->willReturn(1);

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testProcessWhenMessageIsTranslatableAndMessageNotSent()
    {
        $sender = From::emailAddress('from@email.com');
        $messageBody = [
            'toEmail'  => 'to@email.com',
            'sender'   => $sender->toArray(),
            'template' => 'template_name',
            'body'     => ['body_parameter' => 'value'],
            'userId'   => self::USER_ID,
        ];
        $message = new Message();
        $message->setBody(JSON::encode($messageBody));

        $logger = $this->createMock(LoggerInterface::class);
        $templateEmailMessageSender = $this->createMock(TemplateEmailMessageSender::class);
        $processor = new SendEmailMessageProcessor(
            $this->createMock(DirectMailer::class),
            $this->createMock(Processor::class),
            $this->createMock(ManagerRegistry::class),
            $this->createMock(EmailRenderer::class),
            $logger,
            $templateEmailMessageSender
        );

        $templateEmailMessageSender->expects($this->once())
            ->method('isTranslatable')
            ->with(
                $this->callback(function ($message) use ($messageBody) {
                    return $message == array_replace_recursive($message, $messageBody);
                })
            )
            ->willReturn(true);

        $templateEmailMessageSender->expects($this->any())
            ->method('sendTranslatedMessage')
            ->with(
                $this->callback(function ($message) use ($messageBody) {
                    return $message == array_replace_recursive($message, $messageBody);
                })
            )
            ->willReturnCallback(function ($message, &$failedRecipients) {
                $failedRecipients = ['to@email.com'];

                return 0;
            });

        $logger->expects($this->once())
            ->method('error')
            ->with("Cannot send message to the following recipients: Array\n(\n    [0] => to@email.com\n)\n");

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $processor->process($message, $this->createMock(SessionInterface::class))
        );
    }
}
