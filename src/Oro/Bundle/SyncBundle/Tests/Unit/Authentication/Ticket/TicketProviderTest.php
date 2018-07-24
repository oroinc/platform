<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Authentication\Ticket;

use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketDigestGenerator\TicketDigestGeneratorInterface;
use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketProvider;
use Symfony\Component\Security\Core\User\UserInterface;

class TicketProviderTest extends \PHPUnit\Framework\TestCase
{
    private const TICKET_DIGEST = 'sampleDigest';

    /**
     * @var TicketDigestGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $ticketDigestGenerator;

    /**
     * @var string
     */
    private $secret;

    /**
     * @var TicketProvider
     */
    private $ticketProvider;

    protected function setUp()
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
        $this->ticketDigestGenerator
            ->expects(self::once())
            ->method('generateDigest')
            ->with($this->isType('string'), $this->isType('string'), $this->secret)
            ->willReturn(self::TICKET_DIGEST);

        $ticket = $this->ticketProvider->generateTicket();

        $ticket = base64_decode($ticket);
        self::assertNotFalse($ticket);

        [$ticketDigest, $userName, $nonce, $created] = explode(';', $ticket);
        self::assertEquals($ticketDigest, self::TICKET_DIGEST);
        self::assertEmpty($userName);
        self::assertNotEmpty($nonce);
        self::assertNotEmpty($created);
    }

    public function testGenerateTicket(): void
    {
        $user = $this->createMock(UserInterface::class);

        $username = 'sampleUsername';
        $user
            ->expects(self::once())
            ->method('getUsername')
            ->willReturn($username);

        $password = 'samplePassword';
        $user
            ->expects(self::once())
            ->method('getPassword')
            ->willReturn($password);

        $this->ticketDigestGenerator
            ->expects(self::once())
            ->method('generateDigest')
            ->with($this->isType('string'), $this->isType('string'), $password)
            ->willReturn(self::TICKET_DIGEST);

        $ticket = $this->ticketProvider->generateTicket($user);

        $ticket = base64_decode($ticket);
        self::assertNotFalse($ticket);

        [$ticketDigest, $actualUsername, $nonce, $created] = explode(';', $ticket);
        self::assertEquals($ticketDigest, self::TICKET_DIGEST);
        self::assertEquals($username, $actualUsername);
        self::assertNotEmpty($nonce);
        self::assertNotEmpty($created);
    }
}
