<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Authentication\Ticket\TicketDigestGenerator;

use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketDigestGenerator\TicketDigestGenerator;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class TicketDigestGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var PasswordHasherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $passwordHasher;

    /** @var string */
    private $secret;

    /** @var TicketDigestGenerator */
    private $ticketDigestGenerator;

    protected function setUp(): void
    {
        $this->passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $this->secret = 'sampleSecret';

        $this->ticketDigestGenerator = new TicketDigestGenerator($this->passwordHasher, $this->secret);
    }

    public function testGenerateDigest(): void
    {
        $nonce = 'sampleNonce';
        $created = 'sampleCreated';
        $password = 'samplePassword';
        $raw = 'sampleNonce|sampleCreated|samplePassword';

        $expectedDigest = 'sampleDigest';
        $this->passwordHasher->expects(self::once())
            ->method('hash')
            ->with($raw, $this->secret)
            ->willReturn($expectedDigest);

        $actualDigest = $this->ticketDigestGenerator->generateDigest($nonce, $created, $password);

        self::assertSame($expectedDigest, $actualDigest);
    }
}
