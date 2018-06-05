<?php

namespace Oro\Bundle\SyncBundle\EventListener;

use Gos\Bundle\WebSocketBundle\Event\ClientEvent;
use Guzzle\Http\Message\RequestInterface;
use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketProviderInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Authenticate WAMP connection by Sync authentication ticket.
 */
class AuthenticateEventListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var TicketProviderInterface
     */
    private $ticketProvider;

    /**
     * @param TicketProviderInterface $ticketProvider
     */
    public function __construct(TicketProviderInterface $ticketProvider)
    {
        $this->ticketProvider = $ticketProvider;
        $this->logger = new NullLogger();
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

        // Try to find the ticket at requested URL.
        $requestUrl = $request->getUrl(true);
        if ($requestUrl->getQuery()->get('ticket')) {
            $ticket = (string)$requestUrl->getQuery()->get('ticket');
        }

        if ($ticket) {
            $conn->Authenticated = $this->ticketProvider->isTicketValid($ticket);

            $this->logger->debug('Sync ticket was found in the request', \func_get_args());
        } else {
            $this->logger->warning('Sync ticket was not found in the request', \func_get_args());
        }
    }
}
