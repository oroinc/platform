<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Async;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
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
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class SendEmailMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const USER_ID = 24;

    public function testShouldConstructWithRequiredArguments()
    {
        new SendEmailMessageProcessor(
            $this->createMailerMock(),
            $this->createEmailProcessorMock(),
            $this->createManagerRegistryMock(),
            $this->createEmailRendererMock(),
            $this->createLoggerMock(),
            $this->createTemplateEmailMessageSenderMock()
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
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Got invalid message');

        $processor = new SendEmailMessageProcessor(
            $this->createMailerMock(),
            $this->createEmailProcessorMock(),
            $this->createManagerRegistryMock(),
            $this->createEmailRendererMock(),
            $logger,
            $this->createTemplateEmailMessageSenderMock()
        );

        $sender = From::emailAddress('from@email.com');
        $message = new NullMessage;
        $message->setBody(json_encode([
            'toEmail' => 'to@email.com',
            'sender' => $sender->toArray(),
        ]));

        $result = $processor->process($message, $this->createSessionMock());

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectIfSenderNotSet()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Got invalid message');

        $processor = new SendEmailMessageProcessor(
            $this->createMailerMock(),
            $this->createEmailProcessorMock(),
            $this->createManagerRegistryMock(),
            $this->createEmailRendererMock(),
            $logger,
            $this->createTemplateEmailMessageSenderMock()
        );

        $message = new NullMessage;
        $message->setBody(json_encode([
            'body'    => 'body',
            'toEmail' => 'to@email.com',
        ]));

        $result = $processor->process($message, $this->createSessionMock());

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectIfRecepientNotSet()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Got invalid message');

        $processor = new SendEmailMessageProcessor(
            $this->createMailerMock(),
            $this->createEmailProcessorMock(),
            $this->createManagerRegistryMock(),
            $this->createEmailRendererMock(),
            $logger,
            $this->createTemplateEmailMessageSenderMock()
        );

        $sender = From::emailAddress('from@email.com');
        $message = new NullMessage;
        $message->setBody(json_encode([
            'body' => 'body',
            'sender' => $sender->toArray(),
        ]));

        $result = $processor->process($message, $this->createSessionMock());

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectIfTemplatePassedButBodyIsNotArray()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Got invalid message');

        $processor = new SendEmailMessageProcessor(
            $this->createMailerMock(),
            $this->createEmailProcessorMock(),
            $this->createManagerRegistryMock(),
            $this->createEmailRendererMock(),
            $logger,
            $this->createTemplateEmailMessageSenderMock()
        );

        $message = new NullMessage;
        $sender = From::emailAddress('from@email.com');
        $message->setBody(json_encode([
            'sender' => $sender->toArray(),
            'toEmail' => 'to@email.com',
            'template' => 'template_name',
        ]));

        $result = $processor->process($message, $this->createSessionMock());

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectIfSendingFailed()
    {
        $mailer = $this->createMailerMock();
        $mailer
            ->expects($this->once())
            ->method('send')
            ->willReturn(0);

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('Cannot send message');

        $processor = new SendEmailMessageProcessor(
            $mailer,
            $this->createEmailProcessorMock(),
            $this->createManagerRegistryMock(),
            $this->createEmailRendererMock(),
            $logger,
            $this->createTemplateEmailMessageSenderMock()
        );

        $message = new NullMessage;
        $sender = From::emailAddress('from@email.com');
        $message->setBody(json_encode([
            'body' => 'Message body',
            'toEmail' => 'to@email.com',
            'sender' => $sender->toArray()
        ]));

        $result = $processor->process($message, $this->createSessionMock());

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldSendEmailAndReturmACKIfAllParametersCorrect()
    {
        $mailer = $this->createMailerMock();
        $mailer
            ->expects($this->once())
            ->method('send')
            ->willReturn(1);

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->never())
            ->method('critical');

        $processor = new SendEmailMessageProcessor(
            $mailer,
            $this->createEmailProcessorMock(),
            $this->createManagerRegistryMock(),
            $this->createEmailRendererMock(),
            $logger,
            $this->createTemplateEmailMessageSenderMock()
        );

        $message = new NullMessage;
        $sender = From::emailAddress('from@email.com');
        $message->setBody(json_encode([
            'body' => 'Message body',
            'toEmail' => 'to@email.com',
            'sender' => $sender->toArray()
        ]));

        $result = $processor->process($message, $this->createSessionMock());

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldRenderCorrectEmailTemplate()
    {
        $emailTemplate = $this->createMock(EmailTemplate::class);

        $repository = $this->createMock(EmailTemplateRepository::class);
        $repository
            ->expects($this->once())
            ->method('findByName')
            ->with($this->equalTo('template_name'))
            ->willReturn($emailTemplate);

        $manager = $this->createMock(EntityManager::class);
        $manager
            ->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo(EmailTemplate::class))
            ->willReturn($repository);

        $managerRegistry = $this->createManagerRegistryMock();
        $managerRegistry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with($this->equalTo(EmailTemplate::class))
            ->willReturn($manager);

        $emailRenderer = $this->createEmailRendererMock();
        $emailRenderer
            ->expects($this->once())
            ->method('compileMessage')
            ->with($this->isInstanceOf(EmailTemplate::class), $this->equalTo(['body_parameter' => 'value']))
            ->willReturn(['email subject', 'email body']);

        $processor = new SendEmailMessageProcessor(
            $this->createMailerMock(),
            $this->createEmailProcessorMock(),
            $managerRegistry,
            $emailRenderer,
            $this->createLoggerMock(),
            $this->createTemplateEmailMessageSenderMock()
        );

        $message = new NullMessage;
        $sender = From::emailAddress('from@email.com');
        $message->setBody(json_encode([
            'toEmail' => 'to@email.com',
            'sender' => $sender->toArray(),
            'template' => 'template_name',
            'body' => ['body_parameter' => 'value'],
        ]));

        $processor->process($message, $this->createSessionMock());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testShouldThrowExceptionIfTemplateNotFound()
    {
        $repository = $this->createMock(EmailTemplateRepository::class);
        $repository
            ->expects($this->once())
            ->method('findByName')
            ->with($this->equalTo('template_name'))
            ->willReturn(null);

        $manager = $this->createMock(EntityManager::class);
        $manager
            ->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo(EmailTemplate::class))
            ->willReturn($repository);

        $managerRegistry = $this->createManagerRegistryMock();
        $managerRegistry
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with($this->equalTo(EmailTemplate::class))
            ->willReturn($manager);

        $emailRenderer = $this->createEmailRendererMock();
        $emailRenderer
            ->expects($this->never())
            ->method('compileMessage');

        $processor = new SendEmailMessageProcessor(
            $this->createMailerMock(),
            $this->createEmailProcessorMock(),
            $managerRegistry,
            $emailRenderer,
            $this->createLoggerMock(),
            $this->createTemplateEmailMessageSenderMock()
        );

        $message = new NullMessage;
        $sender = From::emailAddress('from@email.com');
        $message->setBody(json_encode([
            'toEmail' => 'to@email.com',
            'sender' => $sender->toArray(),
            'template' => 'template_name',
            'body' => ['body_parameter' => 'value'],
        ]));

        $processor->process($message, $this->createSessionMock());
    }

    public function testProcessWhenMessageIsTranslatableAndMessageSent()
    {
        $templateEmailMessageSender = $this->createTemplateEmailMessageSenderMock();
        $processor = new SendEmailMessageProcessor(
            $this->createMailerMock(),
            $this->createEmailProcessorMock(),
            $this->createManagerRegistryMock(),
            $this->createEmailRendererMock(),
            $this->createLoggerMock(),
            $templateEmailMessageSender
        );

        $sender = From::emailAddress('from@email.com');
        $messageBody = [
            'toEmail' => 'to@email.com',
            'sender' => $sender->toArray(),
            'template' => 'template_name',
            'body' => ['body_parameter' => 'value'],
            'userId' => self::USER_ID
        ];

        $message = new NullMessage;
        $message->setBody(json_encode($messageBody));

        $templateEmailMessageSender
            ->expects($this->any())
            ->method('isTranslatable')
            ->with($this->callback(function ($message) use ($messageBody) {
                return $message == array_replace_recursive($message, $messageBody);
            }))
            ->willReturn(true);

        $templateEmailMessageSender
            ->expects($this->any())
            ->method('sendTranslatedMessage')
            ->with($this->callback(function ($message) use ($messageBody) {
                return $message == array_replace_recursive($message, $messageBody);
            }))
            ->willReturn(1);

        self::assertEquals(MessageProcessorInterface::ACK, $processor->process($message, $this->createSessionMock()));
    }

    public function testProcessWhenMessageIsTranslatableAndMessageNotSent()
    {
        $sender = From::emailAddress('from@email.com');
        $messageBody = [
            'toEmail' => 'to@email.com',
            'sender' => $sender->toArray(),
            'template' => 'template_name',
            'body' => ['body_parameter' => 'value'],
            'userId' => self::USER_ID,
        ];
        $message = new NullMessage;
        $message->setBody(json_encode($messageBody));

        $logger = $this->createLoggerMock();
        $templateEmailMessageSender = $this->createTemplateEmailMessageSenderMock();
        $processor = new SendEmailMessageProcessor(
            $this->createMailerMock(),
            $this->createEmailProcessorMock(),
            $this->createManagerRegistryMock(),
            $this->createEmailRendererMock(),
            $logger,
            $templateEmailMessageSender
        );

        $templateEmailMessageSender
            ->expects($this->once())
            ->method('isTranslatable')
            ->with($this->callback(function ($message) use ($messageBody) {
                return $message == array_replace_recursive($message, $messageBody);
            }))
            ->willReturn(true);

        $templateEmailMessageSender
            ->expects($this->any())
            ->method('sendTranslatedMessage')
            ->with($this->callback(function ($message) use ($messageBody) {
                return $message == array_replace_recursive($message, $messageBody);
            }))
            ->willReturnCallback(function ($message, &$failedRecipients) {
                $failedRecipients = ['to@email.com'];

                return 0;
            });

        $logger
            ->expects($this->once())
            ->method('error')
            ->with("Cannot send message to the following recipients: Array\n(\n    [0] => to@email.com\n)\n");

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $processor->process($message, $this->createSessionMock())
        );
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject | DirectMailer
     */
    private function createMailerMock()
    {
        return $this->createMock(DirectMailer::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject | LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject | TemplateEmailMessageSender
     */
    private function createTemplateEmailMessageSenderMock()
    {
        return $this->createMock(TemplateEmailMessageSender::class);
    }
    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject | Processor
     */
    private function createEmailProcessorMock()
    {
        return $this->createMock(Processor::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry
     */
    private function createManagerRegistryMock()
    {
        return $this->createMock(ManagerRegistry::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|EmailRenderer
     */
    private function createEmailRendererMock()
    {
        return $this->createMock(EmailRenderer::class);
    }
}
