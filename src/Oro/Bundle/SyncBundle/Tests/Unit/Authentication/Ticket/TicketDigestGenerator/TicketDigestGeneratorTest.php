<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Authentication\Ticket\TicketDigestGenerator;

use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketDigestGenerator\TicketDigestGenerator;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class TicketDigestGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PasswordEncoderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $passwordEncoder;

    /**
     * @var string
     */
    private $secret;

    /**
     * @var TicketDigestGenerator
     */
    private $ticketDigestGenerator;

    protected function setUp()
    {
        $this->passwordEncoder = $this->createMock(PasswordEncoderInterface::class);
        $this->secret = 'sampleSecret';

        $this->ticketDigestGenerator = new TicketDigestGenerator($this->passwordEncoder, $this->secret);
    }

    public function testGenerateDigest(): void
    {
        $nonce = 'sampleNonce';
        $created = 'sampleCreated';
        $password = 'samplePassword';
        $raw = 'sampleNonce|sampleCreated|samplePassword';

        $expectedDigest = 'sampleDigest';
        $this
            ->passwordEncoder
            ->expects(self::once())
            ->method('encodePassword')
            ->with($raw, $this->secret)
            ->willReturn($expectedDigest);

        $actualDigest = $this->ticketDigestGenerator->generateDigest($nonce, $created, $password);

        self::assertSame($expectedDigest, $actualDigest);
    }
}
