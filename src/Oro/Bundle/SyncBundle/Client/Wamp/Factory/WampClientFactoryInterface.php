<?php

namespace Oro\Bundle\SyncBundle\Client\Wamp\Factory;

use Oro\Bundle\SyncBundle\Client\Wamp\WampClient;
use Oro\Bundle\SyncBundle\Provider\WebsocketClientParametersProviderInterface;

/**
 * Interface for websocket server client factories.
 */
interface WampClientFactoryInterface
{
    public function createClient(WebsocketClientParametersProviderInterface $clientParametersProvider): WampClient;
}
