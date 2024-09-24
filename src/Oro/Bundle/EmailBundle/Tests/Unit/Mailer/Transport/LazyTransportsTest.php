<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Mailer\Transport;

use Oro\Bundle\EmailBundle\Mailer\Transport\LazyTransports;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportFactoryInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Message;

class LazyTransportsTest extends \PHPUnit\Framework\TestCase
{
    private TransportFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $transportFactory;
    private Transport $transport;

    #[\Override]
    protected function setUp(): void
    {
        $this->transportFactory = $this->createMock(TransportFactoryInterface::class);
        $this->transport = new Transport([$this->transportFactory]);
    }

    public function testConstructDoesNotCreateTransports(): void
    {
        $this->transportFactory
            ->expects(self::never())
            ->method(self::anything());

        new LazyTransports($this->transport, ['main' => 'null://null']);
    }

    public function testSendCreatesTransport(): void
    {
        $transportsDsns = ['main' => 'null://null'];
        $mainTransport = $this->createMock(TransportInterface::class);
        $this->transportFactory
            ->expects(self::once())
            ->method('supports')
            ->willReturn(true);
        $this->transportFactory
            ->expects(self::once())
            ->method('create')
            ->willReturn($mainTransport);

        $message = new Message();
        $envelope = $this->createMock(Envelope::class);
        $expectedSentMessage = $this->createMock(SentMessage::class);
        $mainTransport
            ->expects(self::exactly(2))
            ->method('send')
            ->with($message, $envelope)
            ->willReturn($expectedSentMessage);

        $lazyTransports = new LazyTransports($this->transport, $transportsDsns);

        self::assertSame($expectedSentMessage, $lazyTransports->send($message, $envelope));

        // Checks caching.
        self::assertSame($expectedSentMessage, $lazyTransports->send($message, $envelope));
    }

    public function testToStringDoesNotCreateTransports(): void
    {
        $transportsDsns = ['main' => 'null://null', 'another' => 'native://default'];
        $this->transportFactory
            ->expects(self::never())
            ->method(self::anything());

        $lazyTransports = new LazyTransports($this->transport, $transportsDsns);

        self::assertEquals('[main,another]', (string)$lazyTransports);
    }
}
