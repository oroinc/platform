<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\EventListener;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Event\BeforeMessageEvent;
use Oro\Bundle\EmailBundle\Mailer\Envelope\EmailOriginAwareEnvelope;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\EventListener\SetUserEmailOriginTransportListener;
use Oro\Bundle\ImapBundle\Mailer\Transport\UserEmailOriginTransport;
use Oro\Bundle\ImapBundle\Tests\Unit\Stub\TestUserEmailOrigin;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\Message;

class SetUserEmailOriginTransportListenerTest extends \PHPUnit\Framework\TestCase
{
    private SetUserEmailOriginTransportListener $listener;

    protected function setUp(): void
    {
        $this->listener = new SetUserEmailOriginTransportListener('sample_transport');
    }

    public function testOnBeforeSendDoesNothingIfNoEnvelope(): void
    {
        $event = new BeforeMessageEvent(new Message());

        $this->listener->onBeforeMessage($event);

        $headers = $event->getMessage()->getHeaders();
        self::assertFalse($headers->has('X-Transport'));
        self::assertFalse($headers->has(UserEmailOriginTransport::HEADER_NAME));
    }

    public function testOnBeforeSendDoesNothingIfAnotherEnvelope(): void
    {
        $message = new Message();
        $event = new BeforeMessageEvent($message, Envelope::create($message));

        $this->listener->onBeforeMessage($event);

        $headers = $event->getMessage()->getHeaders();
        self::assertFalse($headers->has('X-Transport'));
        self::assertFalse($headers->has(UserEmailOriginTransport::HEADER_NAME));
    }

    public function testOnBeforeSendDoesNothingIfNoEmailOrigin(): void
    {
        $message = new Message();
        $envelope = EmailOriginAwareEnvelope::create($message);
        $event = new BeforeMessageEvent($message, $envelope);

        $this->listener->onBeforeMessage($event);

        $headers = $event->getMessage()->getHeaders();
        self::assertFalse($headers->has('X-Transport'));
        self::assertFalse($headers->has(UserEmailOriginTransport::HEADER_NAME));
    }

    public function testOnBeforeSendDoesNothingIfNotUserEmailOrigin(): void
    {
        $message = new Message();
        $envelope = EmailOriginAwareEnvelope::create($message);
        $envelope->setEmailOrigin($this->createMock(EmailOrigin::class));
        $event = new BeforeMessageEvent($message, $envelope);

        $this->listener->onBeforeMessage($event);

        $headers = $event->getMessage()->getHeaders();
        self::assertFalse($headers->has('X-Transport'));
        self::assertFalse($headers->has(UserEmailOriginTransport::HEADER_NAME));
    }

    public function testOnBeforeSendDoesNothingIfUserEmailOriginSmtpNotConfigured(): void
    {
        $message = new Message();
        $envelope = EmailOriginAwareEnvelope::create($message);
        $envelope->setEmailOrigin($this->createMock(UserEmailOrigin::class));
        $event = new BeforeMessageEvent($message, $envelope);

        $this->listener->onBeforeMessage($event);

        $headers = $event->getMessage()->getHeaders();
        self::assertFalse($headers->has('X-Transport'));
        self::assertFalse($headers->has(UserEmailOriginTransport::HEADER_NAME));
    }

    public function testOnBeforeSendSetsHeadersIfUserEmailOrigin(): void
    {
        $message = new Message();
        $envelope = EmailOriginAwareEnvelope::create($message);
        $userEmailOrigin = (new TestUserEmailOrigin(42))
            ->setSmtpHost('localhost')
            ->setSmtpPort(25)
            ->setUser('sample_user')
            ->setPassword('sample_password');
        $envelope->setEmailOrigin($userEmailOrigin);
        $event = new BeforeMessageEvent($message, $envelope);

        $this->listener->onBeforeMessage($event);

        $headers = $event->getMessage()->getHeaders();
        self::assertEquals('sample_transport', $headers->get('X-Transport')->getValue());
        self::assertEquals(42, $headers->get(UserEmailOriginTransport::HEADER_NAME)->getValue());
    }
}
