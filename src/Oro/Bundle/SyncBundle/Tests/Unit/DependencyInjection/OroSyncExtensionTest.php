<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\DependencyInjection;

use Monolog\Logger;
use Oro\Bundle\SyncBundle\DependencyInjection\OroSyncExtension;
use Oro\Bundle\SyncBundle\Test\Client\ConnectionChecker;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroSyncExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider loadExceptionProvider
     *
     * @param string $param
     * @param mixed $value
     * @param string $expected
     */
    public function testLoadException(string $param, $value, string $expected): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($expected);

        $container = new ContainerBuilder();
        $container->setParameter($param, $value);

        $extension = new OroSyncExtension();
        $extension->load([], $container);
    }

    public function loadExceptionProvider(): array
    {
        return [
            'transport' => [
                'param' => OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_TRANSPORT,
                'value' => 'unknown',
                'expected' => 'Transport "unknown" is not available, please run stream_get_transports() to verify ' .
                    'the list of registered transports.',
            ],
            'context options' => [
                'param' => OroSyncExtension::CONFIG_PARAM_WEBSOCKET_SSL_CONTEXT_OPTIONS,
                'value' => ['param' => 'unknown'],
                'expected' => 'Unknown socket context option "param". Only SSL context options ' .
                    '(http://php.net/manual/en/context.ssl.php) are allowed.',
            ],
            'context options with invalid value' => [
                'param' => OroSyncExtension::CONFIG_PARAM_WEBSOCKET_SSL_CONTEXT_OPTIONS,
                'value' => ['verify_peer' => 'unknown'],
                'expected' => 'Invalid type "string" of socket context option "verify_peer", expected "boolean" type.',
            ],
        ];
    }

    /**
     * @dataProvider loadWebsocketConnectionParametersDataProvider
     */
    public function testLoadWebsocketConnectionParameters(array $params, array $expected): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', 'prod');

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

        self::assertEquals($expected, $configurationParams);
    }

    public function loadWebsocketConnectionParametersDataProvider(): array
    {
        return [
            [
                'params' => [],
                'expected' => [
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_TRANSPORT => 'tcp',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_SSL_CONTEXT_OPTIONS => [],
                ],
            ],
            [
                'params' => [
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_HOST => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_PORT => '8080',
                ],
                'expected' => [
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_HOST => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_PORT => '8080',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_TRANSPORT => 'tcp',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_SSL_CONTEXT_OPTIONS => [],
                ],
            ],
            [
                'params' => [
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_HOST => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_PORT => '8080',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_PATH => '',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_TRANSPORT => 'ssl',
                ],
                'expected' => [
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_HOST => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_PORT => '8080',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_PATH => '',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BIND_ADDRESS => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BIND_PORT => '8080',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_HOST => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_PORT => '8080',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_PATH => '',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_FRONTEND_HOST => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_FRONTEND_PORT => '8080',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_FRONTEND_PATH => '',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_TRANSPORT => 'ssl',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_SSL_CONTEXT_OPTIONS => [],
                ],
            ],
            [
                'params' => [
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_HOST => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_PORT => '8080',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_PATH => '',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_HOST => '1.1.1.1',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_PORT => '1010',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_FRONTEND_PATH => 'websocket',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_SSL_CONTEXT_OPTIONS => ['peer_name' => 'name'],
                ],
                'expected' => [
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_HOST => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_PORT => '8080',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_DEFAULT_PATH => '',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BIND_ADDRESS => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BIND_PORT => '8080',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_HOST => '1.1.1.1',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_PORT => '1010',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_FRONTEND_HOST => '0.0.0.0',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_FRONTEND_PORT => '8080',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_PATH => '',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_FRONTEND_PATH => 'websocket',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_BACKEND_TRANSPORT => 'tcp',
                    OroSyncExtension::CONFIG_PARAM_WEBSOCKET_SSL_CONTEXT_OPTIONS => ['peer_name' => 'name'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider loadPrependsMonologConfigDataProvider
     */
    public function testLoadPrependsMonologConfig(bool $isDebug, array $expectedVerbosityLevels): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', $isDebug);
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroSyncExtension();
        $extension->load([], $container);

        self::assertEquals([
            [
                'channels' => ['oro_websocket'],
                'handlers' => [
                    'websocket' => [
                        'type' => 'console',
                        'verbosity_levels' => $expectedVerbosityLevels,
                        'channels' => [
                            'type' => 'inclusive',
                            'elements' => ['oro_websocket', 'websocket'],
                        ],
                        'priority' => 512,
                    ],
                ],
            ],
        ], $container->getExtensionConfig('monolog'));

        self::assertEquals(
            ['monolog.handler.websocket', null, 0],
            $container->getDefinition('oro_sync.log.handler.websocket_server_console')
                ->getDecoratedService()
        );
    }

    public function loadPrependsMonologConfigDataProvider(): array
    {
        return [
            [
                'isDebug' => false,
                'expectedVerbosityLevels' => [
                    'VERBOSITY_QUIET' => Logger::ERROR,
                    'VERBOSITY_NORMAL' => Logger::WARNING,
                    'VERBOSITY_VERBOSE' => Logger::NOTICE,
                    'VERBOSITY_VERY_VERBOSE' => Logger::INFO,
                    'VERBOSITY_DEBUG' => Logger::DEBUG,
                ],
            ],
            [
                'isDebug' => true,
                'expectedVerbosityLevels' => [
                    'VERBOSITY_QUIET' => Logger::ERROR,
                    'VERBOSITY_NORMAL' => Logger::INFO,
                    'VERBOSITY_VERBOSE' => Logger::DEBUG,
                    'VERBOSITY_VERY_VERBOSE' => Logger::DEBUG,
                    'VERBOSITY_DEBUG' => Logger::DEBUG,
                ],
            ],
        ];
    }

    public function testLoadWhenTestEnvironment(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', 'test');

        $extension = new OroSyncExtension();
        $extension->load([], $container);

        self::assertEquals(
            ConnectionChecker::class,
            $container->getDefinition('oro_sync.test.client.connection_checker')->getClass()
        );

        self::assertEquals(
            ['oro_sync.client.connection_checker', null, -255],
            $container->getDefinition('oro_sync.test.client.connection_checker')->getDecoratedService()
        );
    }
}
