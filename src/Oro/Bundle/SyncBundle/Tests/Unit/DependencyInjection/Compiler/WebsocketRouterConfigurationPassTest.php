<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\SyncBundle\Tests\Unit\Fixture\WebsocketRouterConfigurationPassStub;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\Tests\Unit\Fixtures\Bundle\TestBundle1\TestBundle1;
use Oro\Component\Config\Tests\Unit\Fixtures\Bundle\TestBundle2\TestBundle2;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class WebsocketRouterConfigurationPassTest extends TestCase
{
    public function testProcess(): void
    {
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                'bundle_1' => TestBundle1::class,
                'bundle_2' => TestBundle2::class,
            ]);

        $resources = [
            [
                'resource' => '../../config/oro/websocket_routing/email_config.yml',
                'type' => 'oro_websocket_routing_yaml',
            ],
            [
                'resource' => '../../config/oro/websocket_routing/entity_config_attribute_import.yml',
                'type' => 'oro_websocket_routing_yaml',
            ],
            [
                'resource' => '../../config/oro/websocket_routing/email_config.yml',
                'type' => 'oro_websocket_routing_yaml',
            ],
            [
                'resource' => '../../config/oro/websocket_routing/entity_config_attribute_import.yml',
                'type' => 'oro_websocket_routing_yaml',
            ],
        ];

        $containerBuilder = $this->createMock(ContainerBuilder::class);
        $containerBuilder->expects(self::once())
            ->method('getParameter')
            ->with('gos_web_socket.router_resources')
            ->willReturn([]);

        $containerBuilder->expects(self::once())
            ->method('setParameter')
            ->with('gos_web_socket.router_resources', $resources);

        $pass = new WebsocketRouterConfigurationPassStub();
        $pass->process($containerBuilder);
    }
}
