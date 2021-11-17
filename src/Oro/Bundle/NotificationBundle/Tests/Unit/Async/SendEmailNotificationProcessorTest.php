<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Async;

use Oro\Bundle\EmailBundle\EmbeddedImages\EmbeddedImagesInSymfonyEmailHandler;
use Oro\Bundle\NotificationBundle\Async\SendEmailNotificationProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email as SymfonyEmail;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SendEmailNotificationProcessorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private MailerInterface|\PHPUnit\Framework\MockObject\MockObject $mailer;

    private ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject $validator;

    private SendEmailNotificationProcessor $processor;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $embeddedImagesInSymfonyEmailHandler = $this->createMock(EmbeddedImagesInSymfonyEmailHandler::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->processor = new SendEmailNotificationProcessor(
            $this->mailer,
            $embeddedImagesInSymfonyEmailHandler,
            $this->validator
        );
        $this->setUpLoggerMock($this->processor);

        $embeddedImagesInSymfonyEmailHandler
            ->expects(self::any())
            ->method('handleEmbeddedImages')
            ->willReturnCallback(
                static fn (SymfonyEmail $symfonyEmail) => $symfonyEmail->html('sample body with images extracted')
            );
    }

    /**
     * @dataProvider processRejectsMessageWhenBodyIsInvalidDataProvider
     *
     * @param array $messageBody
     */
    public function testProcessRejectsMessageWhenBodyIsInvalid(array $messageBody): void
    {
        $this->loggerMock
            ->expects(self::once())
            ->method('critical')
            ->with('Message properties from, toEmail, subject, body were not expected to be empty');

        $this->mailer
            ->expects(self::never())
            ->method(self::anything());

        $message = new Message();
        $message->setBody(json_encode($messageBody));

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function processRejectsMessageWhenBodyIsInvalidDataProvider(): array
    {
        return [
            'empty message body' => [[]],
            'from present' => [['from' => 'from@example.com']],
            'toEmail present' => [['toEmail' => 'to@example.com']],
            'subject present' => [['subject' => 'sample subject']],
            'body present' => [['body' => 'sample body']],
            'from missing' => [
                [
                    'toEmail' => 'to@example.com',
                    'subject' => 'sample subject',
                    'body' => 'sample body',
                ],
            ],
            'toEmail missing' => [
                [
                    'from' => 'from@example.com',
                    'subject' => 'sample subject',
                    'body' => 'sample body',
                ],
            ],
            'subject missing' => [
                [
                    'from' => 'from@example.com',
                    'toEmail' => 'to@example.com',
                    'body' => 'sample body',
                ],
            ],
            'body missing' => [
                [
                    'from' => 'from@example.com',
                    'toEmail' => 'to@example.com',
                    'subject' => 'sample subject',
                ],
            ],
        ];
    }

    /**
     * @dataProvider processSendsEmailDataProvider
     *
     * @param array $messageBody
     * @param SymfonyEmail $expectedSymfonyEmail
     */
    public function testProcessSendsEmailWhenSentCount(array $messageBody, SymfonyEmail $expectedSymfonyEmail): void
    {
        $this->validator
            ->expects(self::exactly(2))
            ->method('validate')
            ->withConsecutive([$messageBody['from']], [$messageBody['toEmail']])
            ->willReturn(new ConstraintViolationList());

        $this->mailer
            ->expects(self::once())
            ->method('send')
            ->with($expectedSymfonyEmail);

        $message = new Message();
        $message->setBody(json_encode($messageBody));

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function processSendsEmailDataProvider(): array
    {
        return [
            'when contentType is not set, body is set as text' => [
                'messageBody' => [
                    'from' => 'from@example.com',
                    'toEmail' => 'to@example.com',
                    'subject' => 'sample subject',
                    'body' => 'sample body',
                ],
                'expectedSymfonyEmail' => (new SymfonyEmail())
                    ->from('from@example.com')
                    ->to('to@example.com')
                    ->subject('sample subject')
                    ->text('sample body'),
            ],
            'when contentType is text/html, body is set as html and images are extracted' => [
                'messageBody' => [
                    'from' => 'from@example.com',
                    'toEmail' => 'to@example.com',
                    'subject' => 'sample subject',
                    'body' => 'sample body',
                    'contentType' => 'text/html',
                ],
                'expectedSymfonyEmail' => (new SymfonyEmail())
                    ->from('from@example.com')
                    ->to('to@example.com')
                    ->subject('sample subject')
                    ->html('sample body with images extracted'),
            ],
        ];
    }

    public function testProcessLogsErrorWhenFromAddressInvalid(): void
    {
        $messageBody = [
            'from' => 'invalid_from',
            'toEmail' => 'to@example.com',
            'subject' => 'sample subject',
            'body' => 'sample body',
        ];

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($messageBody['from'])
            ->willReturn(new ConstraintViolationList([$this->createMock(ConstraintViolation::class)]));

        $this->mailer
            ->expects(self::never())
            ->method(self::anything());

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with('Email address "invalid_from" is not valid');

        $message = new Message();
        $message->setBody(json_encode($messageBody));

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testProcessLogsErrorWhenToEmailAddressInvalid(): void
    {
        $messageBody = [
            'from' => 'from@example.com',
            'toEmail' => 'invalid_to',
            'subject' => 'sample subject',
            'body' => 'sample body',
        ];

        $this->validator
            ->expects(self::exactly(2))
            ->method('validate')
            ->withConsecutive([$messageBody['from']], [$messageBody['toEmail']])
            ->willReturnOnConsecutiveCalls(
                new ConstraintViolationList(),
                new ConstraintViolationList([$this->createMock(ConstraintViolation::class)])
            );

        $this->mailer
            ->expects(self::never())
            ->method(self::anything());

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with('Email address "invalid_to" is not valid');

        $message = new Message();
        $message->setBody(json_encode($messageBody));

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testProcessLogsErrorWhenTransportException(): void
    {
        $messageBody = [
            'from' => '"From Name" <from@example.com>',
            'toEmail' => 'to@example.com',
            'subject' => 'sample subject',
            'body' => 'sample body',
        ];

        $this->validator
            ->expects(self::exactly(2))
            ->method('validate')
            ->withConsecutive([$messageBody['from']], [$messageBody['toEmail']])
            ->willReturn(new ConstraintViolationList());

        $symfonyEmail = (new SymfonyEmail())
            ->from($messageBody['from'])
            ->to('to@example.com')
            ->subject('sample subject')
            ->text('sample body');

        $exception = new \RuntimeException('Sample exception');
        $this->mailer
            ->expects(self::once())
            ->method('send')
            ->with($symfonyEmail)
            ->willThrowException($exception);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'Failed to send an email notification to to@example.com: Sample exception',
                ['exception' => $exception]
            );

        $message = new Message();
        $message->setBody(json_encode($messageBody));

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }
}
