<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Authentication\Ticket;

use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketDigestGenerator\TicketDigestGeneratorInterface;
use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketProvider;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TicketProviderTest extends TestCase
{
    private const TICKET_DIGEST = 'sampleDigest';

    private TicketDigestGeneratorInterface&MockObject $ticketDigestGenerator;
    private string $secret;
    private TicketProvider $ticketProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->ticketDigestGenerator = $this->createMock(TicketDigestGeneratorInterface::class);
        $this->secret = 'sampleSecret';

        $this->ticketProvider = new TicketProvider(
            $this->ticketDigestGenerator,
            $this->secret
        );
    }

    public function testGenerateTicketForAnonymous(): void
    {
        $this->ticketDigestGenerator->expects(self::once())
            ->method('generateDigest')
            ->with($this->isType('string'), $this->isType('string'), $this->secret)
            ->willReturn(self::TICKET_DIGEST);

        $ticket = $this->ticketProvider->generateTicket();

        $ticket = base64_decode($ticket);
        self::assertNotFalse($ticket);

        [$ticketDigest, $userName, $nonce, $created] = explode(';', $ticket);
        self::assertEquals(self::TICKET_DIGEST, $ticketDigest);
        self::assertEmpty($userName);
        self::assertNotEmpty($nonce);
        self::assertNotEmpty($created);
    }

    public function testGenerateTicket(): void
    {
        $user = $this->createMock(AbstractUser::class);

        $username = 'sampleUsername';
        $user->expects(self::once())
            ->method('getUserIdentifier')
            ->willReturn($username);

        $password = 'samplePassword';
        $user->expects(self::once())
            ->method('getPassword')
            ->willReturn($password);

        $this->ticketDigestGenerator->expects(self::once())
            ->method('generateDigest')
            ->with($this->isType('string'), $this->isType('string'), $password)
            ->willReturn(self::TICKET_DIGEST);

        $ticket = $this->ticketProvider->generateTicket($user);

        $ticket = base64_decode($ticket);
        self::assertNotFalse($ticket);

        [$ticketDigest, $actualUsername, $nonce, $created] = explode(';', $ticket);
        self::assertEquals(self::TICKET_DIGEST, $ticketDigest);
        self::assertEquals($username, $actualUsername);
        self::assertNotEmpty($nonce);
        self::assertNotEmpty($created);
    }
}
