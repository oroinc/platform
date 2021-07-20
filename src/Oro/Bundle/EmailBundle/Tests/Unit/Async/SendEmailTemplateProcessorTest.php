<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\EmailBundle\Async\SendEmailTemplateProcessor;
use Oro\Bundle\EmailBundle\Tools\AggregatedEmailTemplatesSender;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SendEmailTemplateProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $validator;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var AggregatedEmailTemplatesSender|\PHPUnit\Framework\MockObject\MockObject */
    private $sender;

    /** @var SendEmailTemplateProcessor */
    private $processor;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->sender = $this->createMock(AggregatedEmailTemplatesSender::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new SendEmailTemplateProcessor($this->validator, $this->doctrineHelper, $this->sender);
        $this->processor->setLogger($this->logger);
    }

    /**
     * @dataProvider bodyExceptionDataProvider
     *
     * @param array $body
     * @param string $expectedMessage
     */
    public function testBodyException(array $body, $expectedMessage): void
    {
        $this->logger->expects($this->once())
            ->method('error')
            ->with($expectedMessage);

        $this->validator->expects($this->any())
            ->method('validate')
            ->willReturnCallback(
                function ($value) {
                    $violationList = $this->createMock(ConstraintViolationList::class);
                    $violationList->expects($this->once())
                        ->method('count')
                        ->willReturn(!$value);

                    return $violationList;
                }
            );

        $message = new Message();
        $message->setBody(\json_encode($body));

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function bodyExceptionDataProvider(): array
    {
        return [
            'no from' => [
                'body' => ['to' => 'test@test.com', 'templateName' => 'test', 'entity' => [\stdClass::class, 42]],
                'expectedMessage' => 'Parameter "from" must contain a valid email address, got "".',
            ],
            'no to' => [
                'body' => ['from' => 'test@test.com', 'templateName' => 'test', 'entity' => [\stdClass::class, 42]],
                'expectedMessage' => 'Could not find required entity with class "stdClass" and id "42".',
            ],
            'no template' => [
                'body' => ['from' => 'test@test.com', 'to' => 'test@test.com', 'entity' => [\stdClass::class, 42]],
                'expectedMessage' => 'Parameter "templateName" must contain a valid template name.',
            ],
            'no entity' => [
                'body' => ['from' => 'test@test.com', 'to' => 'test@test.com', 'templateName' => 'test'],
                'expectedMessage' => 'Could not find required entity with class "" and id "".',
            ],
        ];
    }

    public function testSendException(): void
    {
        $this->sender->expects($this->once())
            ->method('send')
            ->willThrowException(new EntityNotFoundException());

        $this->validator->expects($this->any())
            ->method('validate')
            ->willReturn($this->createMock(ConstraintViolationList::class));

        $this->doctrineHelper->expects($this->any())
            ->method('getEntity')
            ->willReturn(new \stdClass());

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Cannot send email template.', ['exception' => new EntityNotFoundException()]);

        $message = new Message();
        $message->setBody(
            \json_encode(
                [
                    'from' => 'test@test.com',
                    'to' => 'test@test.com',
                    'templateName' => 'test',
                    'entity' => [\stdClass::class, 42]
                ]
            )
        );

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecuteWithMultipleRecipients(): void
    {
        $this->sender->expects($this->once())
            ->method('send');

        $this->validator->expects($this->any())
            ->method('validate')
            ->willReturn($this->createMock(ConstraintViolationList::class));

        $this->doctrineHelper->expects($this->any())
            ->method('getEntity')
            ->willReturn(new \stdClass());

        $this->logger->expects($this->never())
            ->method($this->anything());

        $message = new Message();
        $message->setBody(
            \json_encode(
                [
                    'from' => 'test@test.com',
                    'to' => 'test@test.com',
                    'templateName' => 'test',
                    'entity' => [\stdClass::class, 42]
                ]
            )
        );

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }
}
