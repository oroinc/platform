<?php

namespace Oro\Bundle\SyncBundle\Authentication\Ticket;

/**
 * Interface for ticket providers.
 */
interface TicketProviderInterface
{
    /**
     * @param bool $anonymousTicket
     *
     * @return string
     */
    public function generateTicket(bool $anonymousTicket = false): string;

    /**
     * @param $ticket
     *
     * @return bool
     */
    public function isTicketValid(string $ticket): bool;
}
