<?php

namespace Oro\Bundle\SyncBundle\EventListener;

use Psr\Log\LoggerInterface;

use Guzzle\Http\Message\RequestInterface;

use JDare\ClankBundle\Event\ClientEvent;

use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketProvider;

/**
 * Authenticate WAMP connection by Sync authentication ticket.
 */
class AuthenticateEventListener
{
    /** @var TicketProvider */
    private $ticketProvider;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param TicketProvider $ticketProvider
     * @param LoggerInterface $logger
     */
    public function __construct(TicketProvider $ticketProvider, LoggerInterface $logger)
    {
        $this->ticketProvider = $ticketProvider;
        $this->logger = $logger;
    }

    /**
     * @param ClientEvent $event
     */
    public function onClientConnect(ClientEvent $event)
    {
        $conn = $event->getConnection();
        $conn->Authenticated = false;

        /** @var RequestInterface $request */
        $request = $conn->WebSocket->request;
        $ticket = '';

        // try to find the ticket at requested URL
        $requestUrl = $request->getUrl(true);
        if ($requestUrl->getQuery()->get('ticket')) {
            $ticket = (string)$requestUrl->getQuery()->get('ticket');
        }

        if ($ticket) {
            $this->logger->info(
                'Sync ticket was found in the request',
                ['remoteAddress' => $conn->remoteAddress, 'connectionId' => $conn->resourceId]
            );
            $conn->Authenticated = $this->ticketProvider->isTicketValid($ticket);
        } else {
            $this->logger->warning(
                'Sync ticket was not found in the request',
                ['remoteAddress' => $conn->remoteAddress, 'connectionId' => $conn->resourceId]
            );
        }
    }
}
