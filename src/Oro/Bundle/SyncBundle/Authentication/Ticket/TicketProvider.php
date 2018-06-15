<?php

namespace Oro\Bundle\SyncBundle\Authentication\Ticket;

use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketDigestGenerator\TicketDigestGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Provider for getting Sync authentication tickets.
 */
class TicketProvider implements TicketProviderInterface
{
    /** @var TicketDigestGeneratorInterface */
    private $ticketDigestGenerator;

    /** @var string */
    private $secret;

    /**
     * @param TicketDigestGeneratorInterface $ticketDigestGenerator
     * @param string $secret
     */
    public function __construct(TicketDigestGeneratorInterface $ticketDigestGenerator, string $secret)
    {
        $this->ticketDigestGenerator = $ticketDigestGenerator;
        $this->secret = $secret;
    }

    /**
     * {@inheritDoc}
     */
    public function generateTicket(?UserInterface $user = null): string
    {
        $created = date('c');
        $nonce = substr(md5(uniqid(gethostname() . '_', true)), 0, 16);

        if ($user === null) {
            $userName = '';
            $password = $this->secret;
        } else {
            $password = $user->getPassword();
            $userName = $user->getUsername();
        }

        $passwordDigest = $this->ticketDigestGenerator->generateDigest($nonce, $created, $password);

        return base64_encode(sprintf('%s;%s;%s;%s', $passwordDigest, $userName, $nonce, $created));
    }
}
