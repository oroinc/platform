<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Async\SendEmailTemplateProcessor;
use Oro\Bundle\EmailBundle\Sender\EmailTemplateSender;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SendEmailTemplateProcessorTest extends TestCase
{
    private EntityManagerInterface|MockObject $entityManager;

    private EmailTemplateSender|MockObject $emailTemplateSender;

    private LoggerInterface|MockObject $logger;

    private SendEmailTemplateProcessor $processor;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->emailTemplateSender = $this->createMock(EmailTemplateSender::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->processor = new SendEmailTemplateProcessor(
            $doctrine,
            $this->emailTemplateSender,
            $this->logger
        );
    }

    public function testSendException(): void
    {
        $this->emailTemplateSender->expects(self::once())
            ->method('sendEmailTemplate')
            ->willThrowException(new \Exception());

        $this->entityManager->expects(self::any())
            ->method('find')
            ->with(\stdClass::class, 42)
            ->willReturn(new \stdClass());

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Cannot send email template.', ['exception' => new \Exception()]);

        $message = new Message();
        $messageBody = [
            'from' => 'test@example.com',
            'recipients' => ['test@example.com'],
            'templateName' => 'test',
            'entity' => [\stdClass::class, 42],
        ];
        $message->setBody($messageBody);

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testExecuteWithMultipleRecipients(): void
    {
        $this->emailTemplateSender->expects(self::once())
            ->method('sendEmailTemplate');

        $this->entityManager->expects(self::any())
            ->method('find')
            ->with(\stdClass::class, 42)
            ->willReturn(new \stdClass());

        $this->logger->expects(self::never())
            ->method(self::anything());

        $message = new Message();
        $messageBody = [
            'from' => 'test@example.com',
            'recipients' => ['test@example.com'],
            'templateName' => 'test',
            'entity' => [\stdClass::class, 42],
        ];
        $message->setBody($messageBody);

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }
}
