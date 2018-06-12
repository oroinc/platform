<?php

namespace Oro\Bundle\SyncBundle\Authentication\Ticket;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Interface for Sync authenticaton ticket providers.
 */
interface TicketProviderInterface
{
    /**
     * @param null|UserInterface $user
     *
     * @return string
     */
    public function generateTicket(?UserInterface $user = null): string;
}
