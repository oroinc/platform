<?php

namespace Oro\Bundle\SyncBundle\Authentication\Ticket\TicketDigestGenerator;

/**
 * Interface for generators of tickets digests.
 */
interface TicketDigestGeneratorInterface
{
    public function generateDigest(string $nonce, string $created, string $password): string;
}
