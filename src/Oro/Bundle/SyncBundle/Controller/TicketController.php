<?php

namespace Oro\Bundle\SyncBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Controller that allows to retrieve a new Sync authentication ticket for current logged user.
 */
class TicketController extends Controller
{
    /**
     * Retrieve a new Sync authorize ticket for current logged user.
     */
    public function postAction()
    {
        return new JsonResponse(
            [
                'ticket' => $this->container->get('oro_sync.authentication.ticket_provider')->generateTicket()
            ]
        );
    }
}
