<?php

namespace Oro\Bundle\SyncBundle\Authentication\Ticket\TicketDigestGenerator;

/**
 * Interface for generators of tickets digests.
 */
interface TicketDigestGeneratorInterface
{
    /**
     * @param string $nonce
     * @param string $created
     * @param string $password
     *
     * @return string
     */
    public function generateDigest(string $nonce, string $created, string $password): string;
}
