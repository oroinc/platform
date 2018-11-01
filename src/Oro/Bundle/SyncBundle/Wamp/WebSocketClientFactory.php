<?php

namespace Oro\Bundle\SyncBundle\Wamp;

use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketProvider;

/**
 * Factory to create WebSocket client
 */
class WebSocketClientFactory implements WebSocketClientFactoryInterface
{
    /** @var TicketProvider */
    private $ticketProvider;

    /**
     * WebSocketClientFactory constructor.
     *
     * @param TicketProvider $ticketProvider
     */
    public function __construct(TicketProvider $ticketProvider)
    {
        $this->ticketProvider = $ticketProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function create(WebSocketClientAttributes $clientAttributes): WebSocket
    {
        $pathWithTicket = $this->addAuthenticationTicket($clientAttributes->getPath());

        if ($clientAttributes->getTransport() === 'tcp') {
            return new WebSocket(
                $clientAttributes->getHost(),
                $clientAttributes->getPort(),
                $pathWithTicket
            );
        }

        $options = $clientAttributes->getContextOptions();

        return new WebSocketTLS(
            $clientAttributes->getHost(),
            $clientAttributes->getPort(),
            $pathWithTicket,
            $clientAttributes->getTransport(),
            $options ? ['ssl' => $options] : $options
        );
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function addAuthenticationTicket(string $path): string
    {
        $urlInfo = parse_url($path) + ['path' => '', 'query' => ''];
        parse_str($urlInfo['query'], $query);
        $query += ['ticket' => $this->ticketProvider->generateTicket(true)];

        return $urlInfo['path'] . '?' . http_build_query($query);
    }
}
