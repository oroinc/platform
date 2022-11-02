<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Async\SendEmailTemplateProcessor;
use Oro\Bundle\EmailBundle\Tools\AggregatedEmailTemplatesSender;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class SendEmailTemplateProcessorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private AggregatedEmailTemplatesSender|\PHPUnit\Framework\MockObject\MockObject $aggregatedEmailTemplatesSender;

    private SendEmailTemplateProcessor $processor;

    private EntityManager|\PHPUnit\Framework\MockObject\MockObject $entityManager;

    protected function setUp(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->aggregatedEmailTemplatesSender = $this->createMock(AggregatedEmailTemplatesSender::class);

        $this->processor = new SendEmailTemplateProcessor(
            $managerRegistry,
            $this->aggregatedEmailTemplatesSender
        );
        $this->setUpLoggerMock($this->processor);

        $this->entityManager = $this->createMock(EntityManager::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
    }

    public function testSendException(): void
    {
        $this->aggregatedEmailTemplatesSender
            ->expects(self::once())
            ->method('send')
            ->willThrowException(new EntityNotFoundException());

        $this->entityManager
            ->expects(self::any())
            ->method('find')
            ->with(\stdClass::class, 42)
            ->willReturn(new \stdClass());

        $this->loggerMock->expects(self::once())
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

        $this->entityManager
            ->expects(self::any())
            ->method('find')
            ->with(\stdClass::class, 42)
            ->willReturn(new \stdClass());

        $this->loggerMock->expects(self::never())
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
