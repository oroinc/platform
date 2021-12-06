<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Mailer;

use Oro\Bundle\NotificationBundle\Event\NotificationSentEvent;
use Oro\Bundle\NotificationBundle\Mailer\MassNotificationsMailer;
use Oro\Bundle\NotificationBundle\Model\MassNotificationSender;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address as SymfonyAddress;
use Symfony\Component\Mime\RawMessage;

class MassNotificationsMailerTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private TransportInterface|\PHPUnit\Framework\MockObject\MockObject $transport;

    private EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher;

    private MassNotificationsMailer $mailer;

    protected function setUp(): void
    {
        $this->transport = $this->createMock(TransportInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->mailer = new MassNotificationsMailer($this->transport, $this->eventDispatcher);

        $this->setUpLoggerMock($this->mailer);
    }

    public function testSendWhenNoEnvelopeShouldDispatchEvent(): void
    {
        $message = new RawMessage('sample body');
        $sentMessage = $this->createMock(SentMessage::class);
        $this->transport->expects(self::once())
            ->method('send')
            ->with($message, null)
            ->willReturn($sentMessage);

        $envelope = new Envelope(
            SymfonyAddress::create('from@example.com'),
            SymfonyAddress::createArray(['to@example.com'])
        );
        $sentMessage->expects(self::once())
            ->method('getEnvelope')
            ->willReturn($envelope);

        $this->assertLoggerNotCalled();

        $event = new NotificationSentEvent($message, 1, MassNotificationSender::NOTIFICATION_LOG_TYPE);
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with($event);

        $this->mailer->send($message, null);
    }

    public function testSendShouldDispatchEvent(): void
    {
        $message = new RawMessage('sample body');
        $envelope = new Envelope(
            SymfonyAddress::create('from@example.com'),
            SymfonyAddress::createArray(['to@example.com'])
        );

        $sentMessage = $this->createMock(SentMessage::class);
        $this->transport->expects(self::once())
            ->method('send')
            ->with($message, $envelope)
            ->willReturn($sentMessage);

        $sentMessage->expects(self::once())
            ->method('getEnvelope')
            ->willReturn($envelope);

        $this->assertLoggerNotCalled();

        $event = new NotificationSentEvent($message, 1, MassNotificationSender::NOTIFICATION_LOG_TYPE);
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with($event);

        $this->mailer->send($message, $envelope);
    }

    public function testSendShouldLogTransportExceptionAndDispatchEvent(): void
    {
        $message = new RawMessage('sample body');
        $envelope = new Envelope(
            SymfonyAddress::create('from@example.com'),
            SymfonyAddress::createArray(['to@example.com'])
        );

        $transportException = new TransportException('Invalid recipient');
        $this->transport->expects(self::once())
            ->method('send')
            ->with($message, $envelope)
            ->willThrowException($transportException);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with(
                sprintf(
                    'Failed to send a mass notification message %s: %s',
                    get_debug_type($message),
                    $transportException->getMessage()
                ),
                ['exception' => $transportException]
            );

        $event = new NotificationSentEvent($message, 0, MassNotificationSender::NOTIFICATION_LOG_TYPE);
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with($event);

        $this->mailer->send($message, $envelope);
    }
}
