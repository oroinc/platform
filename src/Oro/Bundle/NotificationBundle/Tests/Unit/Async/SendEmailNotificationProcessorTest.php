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
use Symfony\Component\Mime\Exception\RfcComplianceException;

class SendEmailNotificationProcessorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private MailerInterface|\PHPUnit\Framework\MockObject\MockObject $mailer;

    private SendEmailNotificationProcessor $processor;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $embeddedImagesInSymfonyEmailHandler = $this->createMock(EmbeddedImagesInSymfonyEmailHandler::class);

        $this->processor = new SendEmailNotificationProcessor(
            $this->mailer,
            $embeddedImagesInSymfonyEmailHandler
        );
        $this->setUpLoggerMock($this->processor);

        $embeddedImagesInSymfonyEmailHandler->expects(self::any())
            ->method('handleEmbeddedImages')
            ->willReturnCallback(
                static fn (SymfonyEmail $symfonyEmail) => $symfonyEmail->html('sample body with images extracted')
            );
    }

    /**
     * @dataProvider processSendsEmailDataProvider
     *
     * @param array $messageBody
     * @param SymfonyEmail $expectedSymfonyEmail
     */
    public function testProcessSendsEmailWhenSentCount(array $messageBody, SymfonyEmail $expectedSymfonyEmail): void
    {
        $this->mailer->expects(self::once())
            ->method('send')
            ->with($expectedSymfonyEmail);

        $message = new Message();
        $message->setBody($messageBody);

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
                    'contentType' => 'text/plain',
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

    public function testProcessLogsErrorWhenTransportException(): void
    {
        $messageBody = [
            'from' => '"From Name" <from@example.com>',
            'toEmail' => 'to@example.com',
            'subject' => 'sample subject',
            'body' => 'sample body',
            'contentType' => 'text/plain',
        ];

        $symfonyEmail = (new SymfonyEmail())
            ->from($messageBody['from'])
            ->to('to@example.com')
            ->subject('sample subject')
            ->text('sample body');

        $exception = new \RuntimeException('Sample exception');
        $this->mailer->expects(self::once())
            ->method('send')
            ->with($symfonyEmail)
            ->willThrowException($exception);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                'Failed to send an email notification to to@example.com: Sample exception',
                ['exception' => $exception]
            );

        $message = new Message();
        $message->setBody($messageBody);

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    /**
     * @dataProvider processLogsErrorWhenMessageExceptionProvider
     * @return void
     */
    public function testProcessLogsErrorWhenMessageException(
        array $messageBody,
        \Exception $exception,
        string $errorMessage
    ): void {
        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with($errorMessage, ['exception' => $exception]);

        $message = new Message();
        $message->setBody($messageBody);

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function processLogsErrorWhenMessageExceptionProvider(): array
    {
        $rfcComplianceException = new RfcComplianceException(
            'Email "test@gmail.com." does not comply with addr-spec of RFC 2822.'
        );

        return [
            $rfcComplianceException->getMessage() => [
                'messageBody' => [
                    'from' => '"From Name" <from@example.com>',
                    'toEmail' => 'test@gmail.com.',
                    'subject' => 'sample subject',
                    'body' => 'sample body',
                    'contentType' => 'text/plain',
                ],
                'exception' => $rfcComplianceException,
                'errorMessage' => 'Failed to send an email notification to test@gmail.com.: '.
                    $rfcComplianceException->getMessage(),
            ]
        ];
    }
}
