<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Event;

use Oro\Bundle\EmailBundle\Event\BeforeMessageEvent;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\Address as SymfonyAddress;
use Symfony\Component\Mime\RawMessage;

class BeforeMessageEventTest extends \PHPUnit\Framework\TestCase
{
    public function testGetMessage(): void
    {
        $message = new RawMessage('sample_body');
        $event = new BeforeMessageEvent($message);

        self::assertSame($message, $event->getMessage());
    }

    public function testGetEnvelope(): void
    {
        $message = new RawMessage('sample_body');
        $envelope = new Envelope(
            new SymfonyAddress('sender@example.com'),
            [new SymfonyAddress('recipient@example.com')]
        );
        $event = new BeforeMessageEvent($message, $envelope);

        self::assertSame($envelope, $event->getEnvelope());
    }

    public function testSetEnvelope(): void
    {
        $message = new RawMessage('sample_body');
        $envelope = new Envelope(
            new SymfonyAddress('sender@example.com'),
            [new SymfonyAddress('recipient@example.com')]
        );
        $event = new BeforeMessageEvent($message, $envelope);

        $envelope2 = new Envelope(
            new SymfonyAddress('sender@example.com2'),
            [new SymfonyAddress('recipient@example.com2')]
        );
        $event->setEnvelope($envelope2);

        self::assertSame($envelope2, $event->getEnvelope());
    }
}
