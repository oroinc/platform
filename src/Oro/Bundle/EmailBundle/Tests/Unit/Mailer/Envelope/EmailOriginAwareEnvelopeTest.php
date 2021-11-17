<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Mailer\Envelope;

use Oro\Bundle\EmailBundle\Mailer\Envelope\EmailOriginAwareEnvelope;
use Oro\Bundle\ImapBundle\Tests\Unit\Stub\TestUserEmailOrigin;
use Symfony\Component\Mime\Address as SymfonyAddress;
use Symfony\Component\Mime\Message;

class EmailOriginAwareEnvelopeTest extends \PHPUnit\Framework\TestCase
{
    public function testCanBeConstructedWithoutEmailOrigin(): void
    {
        $envelope = new EmailOriginAwareEnvelope(new Message());

        self::assertNull($envelope->getEmailOrigin());
    }

    public function testCanBeConstructedWithEmailOrigin(): void
    {
        $emailOrigin = new TestUserEmailOrigin();
        $envelope = new EmailOriginAwareEnvelope(new Message(), $emailOrigin);

        self::assertSame($emailOrigin, $envelope->getEmailOrigin());
    }

    public function testCreate(): void
    {
        $message = new Message();

        self::assertEquals(new EmailOriginAwareEnvelope($message), EmailOriginAwareEnvelope::create($message));
    }

    public function testGetSender(): void
    {
        $message = new Message();
        $message
            ->getHeaders()
            ->addHeader('From', ['from@example.com']);
        $envelope = new EmailOriginAwareEnvelope($message);

        self::assertEquals(SymfonyAddress::create('from@example.com'), $envelope->getSender());
    }

    public function testGetRecipients(): void
    {
        $message = new Message();
        $message
            ->getHeaders()
            ->addHeader('To', ['to@example.com']);
        $envelope = new EmailOriginAwareEnvelope($message);

        self::assertEquals([SymfonyAddress::create('to@example.com')], $envelope->getRecipients());
    }

    public function testSetEmailOrigin(): void
    {
        $envelope = new EmailOriginAwareEnvelope(new Message(), new TestUserEmailOrigin());

        $newEmailOrigin = new TestUserEmailOrigin();
        $envelope->setEmailOrigin($newEmailOrigin);

        self::assertSame($newEmailOrigin, $envelope->getEmailOrigin());
    }

    public function testSetSender(): void
    {
        $envelope = new EmailOriginAwareEnvelope(new Message(), new TestUserEmailOrigin());

        $sender = SymfonyAddress::create('sender1@example.com');
        $envelope->setSender($sender);

        self::assertEquals($sender, $envelope->getSender());
    }

    public function testSetRecipients(): void
    {
        $envelope = new EmailOriginAwareEnvelope(new Message(), new TestUserEmailOrigin());

        $recipient = SymfonyAddress::create('recipient@example.com');
        $envelope->setRecipients([$recipient]);

        self::assertEquals([$recipient], $envelope->getRecipients());
    }
}
