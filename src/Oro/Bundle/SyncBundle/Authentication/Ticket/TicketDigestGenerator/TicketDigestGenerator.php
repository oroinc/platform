<?php

namespace Oro\Bundle\SyncBundle\Authentication\Ticket\TicketDigestGenerator;

use Symfony\Component\PasswordHasher\PasswordHasherInterface;

/**
 * Generates unique and secure hash which is used in Sync authentication ticket.
 */
class TicketDigestGenerator implements TicketDigestGeneratorInterface
{
    /**
     * @var PasswordHasherInterface
     */
    private $passwordHasher;

    /**
     * @var string
     */
    private $secret;

    public function __construct(PasswordHasherInterface $passwordHasher, string $secret)
    {
        $this->passwordHasher = $passwordHasher;
        $this->secret = $secret;
    }

    /**
     * {@inheritdoc}
     */
    public function generateDigest(string $nonce, string $created, string $password): string
    {
        return $this->passwordHasher->hash(sprintf('%s|%s|%s', $nonce, $created, $password), $this->secret);
    }
}
