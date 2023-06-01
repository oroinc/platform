<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Fixture;

use Oro\Bundle\SyncBundle\DependencyInjection\Compiler\WebsocketRouterConfigurationPass;

/**
 * Websocket router configuration pass stub for testing purposes
 */
class WebsocketRouterConfigurationPassStub extends WebsocketRouterConfigurationPass
{
    protected function getAppConfigPath(): string
    {
        return '../../config/oro/websocket_routing';
    }
}
