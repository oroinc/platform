<?php

namespace Oro\Bundle\SyncBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller that allows to retrieve a new Sync authentication ticket for currently authenticated user.
 */
class TicketController extends Controller
{
    /**
     * Retrieve a new Sync authorize ticket for currently authenticated user.
     *
     * @Route("/sync/ticket", name="oro_sync_ticket", methods={"POST"})
     * @CsrfProtection()
     *
     * @return JsonResponse
     */
    public function syncTicketAction()
    {
        $ticketProvider = $this->get('oro_sync.authentication.ticket_provider');

        return new JsonResponse(['ticket' => $ticketProvider->generateTicket($this->getUser())]);
    }
}
