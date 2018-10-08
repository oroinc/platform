<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SyncBundle\DependencyInjection\OroSyncExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroSyncExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider loadExceptionProvider
     *
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     *
     * @param string $param
     * @param mixed $value
     * @param string $expected
     */
    public function testLoadException(string $param, $value, string $expected)
    {
        $this->expectExceptionMessage($expected);

        $container = new ContainerBuilder();
        $container->setParameter($param, $value);

        $extension = new OroSyncExtension();
        $extension->load([], $container);
    }

    /**
     * @return array
     */
    public function loadExceptionProvider(): array
    {
        return [
            'transport' => [
                'param' => OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_TRANSPORT,
                'value' => 'unknown',
                'expected' => 'Transport "unknown" is not available, please run stream_get_transports() to verify ' .
                    'the list of registered transports.'
            ],
            'context options' => [
                'param' => OroSyncExtension::CONFIG_PARAM_WEBSOCKET_SSL_CONTEXT_OPTIONS,
                'value' => ['param' => 'unknown'],
                'expected' => 'Unknown socket context option "param". Only SSL context options ' .
                    '(http://php.net/manual/en/context.ssl.php) are allowed.'
            ],
            'context options with invalid value' => [
                'param' => OroSyncExtension::CONFIG_PARAM_WEBSOCKET_SSL_CONTEXT_OPTIONS,
                'value' => ['verify_peer' => 'unknown'],
                'expected' => 'Invalid type "string" of socket context option "verify_peer", expected "boolean" type.'
            ]
        ];
    }

    /**
     * @dataProvider loadDataProvider
     *
     * @param array $params
     * @param array $expected
     */
    public function testLoad(array $params, array $expected)
    {
        $container = new ContainerBuilder();

        foreach ($params as $name => $value) {
            $container->setParameter($name, $value);
        }

        $extension = new OroSyncExtension();
        $extension->load([], $container);

        $configurationParams = $container->getParameterBag()->all();

        $websocketParams = [
            OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_HOST,
            OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_PORT,
            OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_PATH,
            OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BIND_ADDRESS,
            OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BIND_PORT,
            OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_HOST,
            OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_PORT,
            OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_PATH,
            OroSyncExtension::CONFIG_PARAM_WEBSOCKET_FRONTEND_HOST,
            OroSyncExtension::CONFIG_PARAM_WEBSOCKET_FRONTEND_PORT,
            OroSyncExtension::CONFIG_PARAM_WEBSOCKET_FRONTEND_PATH,
            OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_TRANSPORT,
            OroSyncExtension::CONFIG_PARAM_WEBSOCKET_SSL_CONTEXT_OPTIONS,
        ];
        $configurationParams = array_intersect_key($configurationParams, array_flip($websocketParams));

        $this->assertEquals($expected, $configurationParams);
    }

    /**
     * @return array
     */
    public function loadDataProvider(): array
    {
        return [
            [
                'params'   => [],
                'expected' => [
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_TRANSPORT => 'tcp',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_SSL_CONTEXT_OPTIONS => []
                ]
            ],
            [
                'params'   => [
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_HOST  => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_PORT  => '8080',
                ],
                'expected' => [
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_HOST  => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_PORT  => '8080',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_TRANSPORT => 'tcp',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_SSL_CONTEXT_OPTIONS => []
                ]
            ],
            [
                'params'   => [
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_HOST  => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_PORT  => '8080',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_PATH  => '',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_TRANSPORT => 'ssl',
                ],
                'expected' => [
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_HOST => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_PORT => '8080',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_PATH => '',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BIND_ADDRESS  => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BIND_PORT     => '8080',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_HOST  => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_PORT  => '8080',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_PATH => '',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_FRONTEND_HOST => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_FRONTEND_PORT => '8080',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_FRONTEND_PATH => '',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_TRANSPORT => 'ssl',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_SSL_CONTEXT_OPTIONS => []
                ]
            ],
            [
                'params'   => [
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_HOST  => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_PORT  => '8080',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_PATH  => '',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_HOST  => '1.1.1.1',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_PORT  => '1010',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_FRONTEND_PATH => 'websocket',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_SSL_CONTEXT_OPTIONS => ['peer_name' => 'name']
                ],
                'expected' => [
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_HOST => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_PORT => '8080',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_PATH => '',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BIND_ADDRESS  => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BIND_PORT     => '8080',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_HOST  => '1.1.1.1',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_PORT  => '1010',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_FRONTEND_HOST => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_FRONTEND_PORT => '8080',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_PATH  => '',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_FRONTEND_PATH => 'websocket',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_TRANSPORT => 'tcp',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_SSL_CONTEXT_OPTIONS => ['peer_name' => 'name']
                ]
            ],
        ];
    }
}
