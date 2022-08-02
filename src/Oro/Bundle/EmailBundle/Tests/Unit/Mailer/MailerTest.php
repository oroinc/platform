<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Mailer;

use Oro\Bundle\EmailBundle\Mailer\Mailer;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Address as SymfonyAddress;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Header\MailboxListHeader;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\Part\MessagePart;
use Symfony\Component\Mime\RawMessage;

class MailerTest extends \PHPUnit\Framework\TestCase
{
    private TransportInterface|\PHPUnit\Framework\MockObject\MockObject $transport;

    private Mailer $mailer;

    protected function setUp(): void
    {
        $this->transport = $this->createMock(TransportInterface::class);

        $this->mailer = new Mailer($this->transport);
    }

    public function testSendWithoutEnvelope(): void
    {
        $message = new RawMessage('sample body');

        $this->transport->expects(self::once())
            ->method('send')
            ->with($message, null)
            ->willReturn($this->createMock(SentMessage::class));

        $this->mailer->send($message, null);
    }

    public function testSendWithEnvelope(): void
    {
        $message = new RawMessage('sample body');
        $envelope = new Envelope(new SymfonyAddress('from@example.com'), [new SymfonyAddress('to@example.com')]);

        $this->transport->expects(self::once())
            ->method('send')
            ->with($message, $envelope)
            ->willReturn($this->createMock(SentMessage::class));

        $this->mailer->send($message, $envelope);
    }

    public function testSendWithMessageObjectShouldHaveRealMessageId(): void
    {
        $messageId = 'c9c159856ded379eb75222de5d246041@example.com';

        $toHeader = new MailboxListHeader('To', [new Address('test@test.com', 'test')]);
        $fromHeader = new MailboxListHeader('From', [new Address('test@test.com', 'test')]);
        $headers = new Headers($toHeader, $fromHeader);
        $message = new Message($headers, new MessagePart(new RawMessage('sample body')));
        $envelope = new Envelope(new SymfonyAddress('from@example.com'), [new SymfonyAddress('to@example.com')]);

        $sentMessage = new SentMessage($message, $envelope);
        $sentMessage->setMessageId($messageId);

        $this->transport->expects(self::once())
            ->method('send')
            ->with($message, $envelope)
            ->willReturn($sentMessage);

        $this->mailer->send($message, $envelope);

        self::assertEquals(
            '<' . $messageId . '>',
            $message->getHeaders()->get('Message-ID')->getBodyAsString()
        );
    }
}
