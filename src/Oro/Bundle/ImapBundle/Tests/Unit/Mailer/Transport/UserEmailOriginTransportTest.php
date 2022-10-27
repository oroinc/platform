<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Mailer\Transport;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Mailer\Transport\DsnFromUserEmailOriginFactory;
use Oro\Bundle\ImapBundle\Mailer\Transport\UserEmailOriginTransport;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address as SymfonyAddress;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\RawMessage;

class UserEmailOriginTransportTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY_ID = 42;

    /** @var Transport|\PHPUnit\Framework\MockObject\MockObject */
    private $transportFactory;

    /** @var DsnFromUserEmailOriginFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $dsnFromUserEmailOriginFactory;

    /** @var UserEmailOrigin|\PHPUnit\Framework\MockObject\MockObject */
    private $userEmailOrigin;

    /** @var UserEmailOriginTransport */
    private $transport;

    protected function setUp(): void
    {
        $this->transportFactory = $this->createMock(Transport::class);
        $this->dsnFromUserEmailOriginFactory = $this->createMock(DsnFromUserEmailOriginFactory::class);
        $this->userEmailOrigin = $this->createMock(UserEmailOrigin::class);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects(self::any())
            ->method('find')
            ->willReturnMap([
                [UserEmailOrigin::class, self::ENTITY_ID, null, null, $this->userEmailOrigin],
            ]);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(UserEmailOrigin::class)
            ->willReturn($entityManager);

        $this->transport = new UserEmailOriginTransport(
            $this->transportFactory,
            $doctrine,
            $this->dsnFromUserEmailOriginFactory,
            new RequestStack()
        );
    }

    public function testToString(): void
    {
        self::assertEquals('<transport based on user email origin>', (string)$this->transport);
    }

    public function testSendThrowsExceptionWhenNotMessage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Message was expected to be an instance of "%s" at this point, got "%s"',
            Message::class,
            RawMessage::class
        ));

        $this->transport->send(new RawMessage('sample_body'));
    }

    public function testSendThrowsTransportExceptionWhenNoRequiredHeader(): void
    {
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Header X-User-Email-Origin-Id was expected to be set');

        $this->transport->send(new Message());
    }

    public function testSendThrowsExceptionWhenRequiredHeaderEmpty(): void
    {
        $message = new Message();
        $message->getHeaders()->addHeader(UserEmailOriginTransport::HEADER_NAME, '');

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Header X-User-Email-Origin-Id was expected to be set');

        $this->transport->send($message);
    }

    public function testSendThrowsExceptionWhenHeaderNotNumeric(): void
    {
        $message = new Message();
        $message->getHeaders()->addHeader(UserEmailOriginTransport::HEADER_NAME, 'sample_string');

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage(
            'Header X-User-Email-Origin-Id was expected to contain numeric id, got "sample_string"'
        );

        $this->transport->send($message);
    }

    public function testSendThrowsExceptionWhenUserEmailOriginNotFound(): void
    {
        $message = new Message();
        $id = 99;
        $message->getHeaders()->addHeader(UserEmailOriginTransport::HEADER_NAME, $id);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage(sprintf('UserEmailOrigin #"%d" is not found', $id));

        $this->transport->send($message);
    }

    public function testSendThrowsExceptionWhenUserEmailOriginNotSmtpConfigured(): void
    {
        $message = new Message();
        $message->getHeaders()->addHeader(UserEmailOriginTransport::HEADER_NAME, self::ENTITY_ID);

        $this->userEmailOrigin->expects(self::once())
            ->method('isSmtpConfigured')
            ->willReturn(false);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage(sprintf(
            'UserEmailOrigin #"%d" was expected to have configured SMTP settings',
            self::ENTITY_ID
        ));

        $this->transport->send($message);
    }

    public function testSend(): void
    {
        $message = new Message();
        $message->getHeaders()->addHeader(UserEmailOriginTransport::HEADER_NAME, self::ENTITY_ID);
        $envelope = new Envelope(new SymfonyAddress('foo@example.org'), [new SymfonyAddress('bar@example.org')]);

        $this->userEmailOrigin->expects(self::once())
            ->method('isSmtpConfigured')
            ->willReturn(true);

        $dsn = new Dsn('scheme', 'host');
        $this->dsnFromUserEmailOriginFactory->expects(self::once())
            ->method('create')
            ->with($this->userEmailOrigin)
            ->willReturn($dsn);

        $configuredTransport = $this->createMock(TransportInterface::class);
        $this->transportFactory->expects(self::once())
            ->method('fromDsnObject')
            ->with($dsn)
            ->willReturn($configuredTransport);

        $configuredTransport->expects(self::exactly(2))
            ->method('send')
            ->with($message, $envelope);

        $this->transport->send($message, $envelope);

        self::assertFalse($message->getHeaders()->has(UserEmailOriginTransport::HEADER_NAME));

        $message->getHeaders()->addHeader(UserEmailOriginTransport::HEADER_NAME, self::ENTITY_ID);

        // Checks local cache.
        $this->transport->send($message, $envelope);
    }
}
