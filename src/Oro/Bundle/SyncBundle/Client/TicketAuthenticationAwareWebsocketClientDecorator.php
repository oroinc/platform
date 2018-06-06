<?php

namespace Oro\Bundle\SyncBundle\Client;

use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketProviderInterface;

/**
 * Adds ticket-authentication facility to websocket client.
 */
class TicketAuthenticationAwareWebsocketClientDecorator extends AbstractWebsocketClientDecorator
{
    /**
     * @var TicketProviderInterface
     */
    private $ticketProvider;

    /**
     * @param WebsocketClientInterface $decoratedClient
     * @param TicketProviderInterface  $ticketProvider
     */
    public function __construct(
        WebsocketClientInterface $decoratedClient,
        TicketProviderInterface $ticketProvider
    ) {
        parent::__construct($decoratedClient);

        $this->ticketProvider = $ticketProvider;
    }

    /**
     * @param string $target
     *
     * @return string
     */
    public function connect(string $target = '/'): ?string
    {
        $urlInfo = parse_url($target) + ['path' => '', 'query' => ''];
        parse_str($urlInfo['query'], $query);
        $query['ticket'] = $this->ticketProvider->generateTicket(true);
        $targetWithTicket = sprintf('%s?%s', $urlInfo['path'], http_build_query($query));

        return $this->decoratedClient->connect($targetWithTicket);
    }
}
