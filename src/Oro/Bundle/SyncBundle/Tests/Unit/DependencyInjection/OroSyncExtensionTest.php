<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\SyncBundle\DependencyInjection\OroSyncExtension;

class OroSyncExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider loadDataProvider
     */
    public function testLoad($params, $expected)
    {
        $container = new ContainerBuilder();

        foreach ($params as $name => $value) {
            $container->setParameter($name, $value);
        }

        $extension = new OroSyncExtension();
        $extension->load([], $container);

        $configurationParams = $container->getParameterBag()->all();

        $websocketParams = [
            OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BIND_ADDRESS,
            OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BIND_PORT,
            OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_HOST,
            OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_PORT,
            OroSyncExtension::CONFIG_PARAM_WEBSOCKET_FRONTEND_HOST,
            OroSyncExtension::CONFIG_PARAM_WEBSOCKET_FRONTEND_PORT
        ];
        $configurationParams = array_intersect_key($configurationParams, array_flip($websocketParams));

        $this->assertEquals($configurationParams, $expected);
    }

    public function loadDataProvider()
    {
        return [
            [
                'params'   => [],
                'expected' => []
            ],
            [
                'params'   => [
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_HOST  => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_PORT  => '8080',
                ],
                'expected' => [
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_HOST  => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_PORT  => '8080',
                ]
            ],
            [
                'params'   => [
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_HOST  => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_PORT  => '8080'
                ],
                'expected' => [
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BIND_ADDRESS  => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BIND_PORT     => '8080',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_HOST  => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_PORT  => '8080',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_FRONTEND_HOST => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_FRONTEND_PORT => '8080',
                ]
            ],
            [
                'params'   => [
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_HOST  => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_PORT  => '8080',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_HOST  => '1.1.1.1',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_PORT  => '1010'
                ],
                'expected' => [
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BIND_ADDRESS  => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BIND_PORT     => '8080',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_HOST  => '1.1.1.1',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_PORT  => '1010',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_FRONTEND_HOST => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_FRONTEND_PORT => '8080',
                ]
            ]
        ];
    }
}
