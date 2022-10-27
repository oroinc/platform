<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Mailer\Transport;

use Oro\Bundle\EmailBundle\Mailer\Transport\LazyTransports;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport as TransportFactory;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mailer\Transport\Transports;
use Symfony\Component\Mime\Message;

class LazyTransportsTest extends \PHPUnit\Framework\TestCase
{
    private TransportFactory|\PHPUnit\Framework\MockObject\MockObject $transportFactory;

    protected function setUp(): void
    {
        $this->transportFactory = $this->createMock(TransportFactory::class);
    }

    public function testConstructDoesNotCreateTransports(): void
    {
        $this->transportFactory
            ->expects(self::never())
            ->method(self::anything());

        new LazyTransports($this->transportFactory, ['main' => 'null://null']);
    }

    public function testSendCreatesTransport(): void
    {
        $transportsDsns = ['main' => 'null://null'];
        $mainTransport = $this->createMock(TransportInterface::class);
        $transports = new Transports(['main' => $mainTransport]);
        $this->transportFactory
            ->expects(self::once())
            ->method('fromStrings')
            ->with($transportsDsns)
            ->willReturn($transports);

        $message = new Message();
        $envelope = $this->createMock(Envelope::class);
        $expectedSentMessage = $this->createMock(SentMessage::class);
        $mainTransport
            ->expects(self::exactly(2))
            ->method('send')
            ->with($message, $envelope)
            ->willReturn($expectedSentMessage);

        $lazyTransports = new LazyTransports($this->transportFactory, $transportsDsns);

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

        $lazyTransports = new LazyTransports($this->transportFactory, $transportsDsns);

        self::assertEquals('[main,another]', (string)$lazyTransports);
    }
}
