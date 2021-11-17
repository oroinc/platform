<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Mailer;

use Oro\Bundle\EmailBundle\Mailer\Mailer;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address as SymfonyAddress;
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
}
