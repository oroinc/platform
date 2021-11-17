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
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SendEmailTemplateProcessorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject $validator;

    private AggregatedEmailTemplatesSender|\PHPUnit\Framework\MockObject\MockObject $aggregatedEmailTemplatesSender;

    private SendEmailTemplateProcessor $processor;

    private EntityManager|\PHPUnit\Framework\MockObject\MockObject $entityManager;

    protected function setUp(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->aggregatedEmailTemplatesSender = $this->createMock(AggregatedEmailTemplatesSender::class);

        $this->processor = new SendEmailTemplateProcessor(
            $managerRegistry,
            $this->validator,
            $this->aggregatedEmailTemplatesSender
        );
        $this->setUpLoggerMock($this->processor);

        $this->entityManager = $this->createMock(EntityManager::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
    }

    /**
     * @dataProvider bodyExceptionDataProvider
     */
    public function testBodyException(array $body, string $expectedMessage): void
    {
        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with($expectedMessage);

        $this->validator->expects(self::any())
            ->method('validate')
            ->willReturnCallback(function ($value) {
                $violationList = $this->createMock(ConstraintViolationList::class);
                $violationList->expects($this->once())
                    ->method('count')
                    ->willReturn(!$value);

                return $violationList;
            });

        $message = new Message();
        $message->setBody(json_encode($body, JSON_THROW_ON_ERROR));

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function bodyExceptionDataProvider(): array
    {
        return [
            'no from' => [
                'body' => [
                    'recipients' => ['test@example.com'],
                    'templateName' => 'test',
                    'entity' => [\stdClass::class, 42],
                ],
                'expectedMessage' => 'Parameter "from" must contain a valid email address, got "".',
            ],
            'no recipients' => [
                'body' => ['from' => 'test@example.com', 'templateName' => 'test', 'entity' => [\stdClass::class, 42]],
                'expectedMessage' => 'Recipients list is empty',
            ],
            'no template' => [
                'body' => [
                    'from' => 'test@example.com',
                    'recipients' => ['test@example.com'],
                    'entity' => [\stdClass::class, 42],
                ],
                'expectedMessage' => 'Parameter "templateName" must contain a valid template name.',
            ],
            'no entity' => [
                'body' => [
                    'from' => 'test@example.com',
                    'recipients' => ['test@example.com'],
                    'templateName' => 'test',
                ],
                'expectedMessage' => 'Parameter "entity" must be an array [string $entityClass, int $entityId],'
                    . ' got "[]".',
            ],
        ];
    }

    public function testSendException(): void
    {
        $this->aggregatedEmailTemplatesSender
            ->expects(self::once())
            ->method('send')
            ->willThrowException(new EntityNotFoundException());

        $this->validator->expects(self::any())
            ->method('validate')
            ->willReturn($this->createMock(ConstraintViolationList::class));

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
        $message->setBody(json_encode($messageBody, JSON_THROW_ON_ERROR));

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testExecuteWithMultipleRecipients(): void
    {
        $this->aggregatedEmailTemplatesSender->expects(self::once())
            ->method('send');

        $this->validator->expects(self::any())
            ->method('validate')
            ->willReturn($this->createMock(ConstraintViolationList::class));

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
        $message->setBody(json_encode($messageBody, JSON_THROW_ON_ERROR));

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }
}
