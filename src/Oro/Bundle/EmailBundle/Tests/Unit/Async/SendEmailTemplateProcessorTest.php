<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Async\SendEmailTemplateProcessor;
use Oro\Bundle\EmailBundle\Tools\AggregatedEmailTemplatesSender;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class SendEmailTemplateProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var AggregatedEmailTemplatesSender|\PHPUnit\Framework\MockObject\MockObject */
    private $aggregatedEmailTemplatesSender;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var SendEmailTemplateProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->aggregatedEmailTemplatesSender = $this->createMock(AggregatedEmailTemplatesSender::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->processor = new SendEmailTemplateProcessor(
            $doctrine,
            $this->aggregatedEmailTemplatesSender,
            $this->logger
        );
    }

    public function testSendException(): void
    {
        $this->aggregatedEmailTemplatesSender->expects(self::once())
            ->method('send')
            ->willThrowException(new EntityNotFoundException());

        $this->entityManager->expects(self::any())
            ->method('find')
            ->with(\stdClass::class, 42)
            ->willReturn(new \stdClass());

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Cannot send email template.', ['exception' => new EntityNotFoundException()]);

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
        $this->aggregatedEmailTemplatesSender->expects(self::once())
            ->method('send');

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
