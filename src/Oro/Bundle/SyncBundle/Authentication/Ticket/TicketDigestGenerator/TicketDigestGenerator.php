<?php

namespace Oro\Bundle\SyncBundle\Authentication\Ticket\TicketDigestGenerator;

use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

/**
 * Generates unique and secure hash which is used in Sync authentication ticket.
 */
class TicketDigestGenerator implements TicketDigestGeneratorInterface
{
    /**
     * @var PasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var string
     */
    private $secret;

    /**
     * @param PasswordEncoderInterface $passwordEncoder
     * @param string $secret
     */
    public function __construct(PasswordEncoderInterface $passwordEncoder, string $secret)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->secret = $secret;
    }

    /**
     * {@inheritdoc}
     */
    public function generateDigest(string $nonce, string $created, string $password): string
    {
        return $this->passwordEncoder->encodePassword(sprintf('%s|%s|%s', $nonce, $created, $password), $this->secret);
    }
}
