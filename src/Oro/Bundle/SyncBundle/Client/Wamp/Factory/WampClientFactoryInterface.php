<?php

namespace Oro\Bundle\SyncBundle\Client\Wamp\Factory;

use Oro\Bundle\SyncBundle\Client\Wamp\WampClient;

/**
 * Interface for websocket server client factories.
 */
interface WampClientFactoryInterface
{
    /**
     * @param ClientAttributes $clientAttributes
     *
     * @return WampClient
     */
    public function createClient(ClientAttributes $clientAttributes): WampClient;
}
