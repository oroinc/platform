<?php

namespace Oro\Bundle\SyncBundle\DependencyInjection;

use Monolog\Logger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class OroSyncExtension extends Extension
{
    const CONFIG_PARAM_WEBSOCKET_DEFAULT_HOST  = 'websocket_host';
    const CONFIG_PARAM_WEBSOCKET_DEFAULT_PORT  = 'websocket_port';
    const CONFIG_PARAM_WEBSOCKET_DEFAULT_PATH  = 'websocket_path';
    const CONFIG_PARAM_WEBSOCKET_BIND_ADDRESS  = 'websocket_bind_address';
    const CONFIG_PARAM_WEBSOCKET_BIND_PORT     = 'websocket_bind_port';
    const CONFIG_PARAM_WEBSOCKET_BACKEND_HOST  = 'websocket_backend_host';
    const CONFIG_PARAM_WEBSOCKET_BACKEND_PORT  = 'websocket_backend_port';
    const CONFIG_PARAM_WEBSOCKET_BACKEND_PATH  = 'websocket_backend_path';
    const CONFIG_PARAM_WEBSOCKET_FRONTEND_HOST = 'websocket_frontend_host';
    const CONFIG_PARAM_WEBSOCKET_FRONTEND_PORT = 'websocket_frontend_port';
    const CONFIG_PARAM_WEBSOCKET_FRONTEND_PATH = 'websocket_frontend_path';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('services.yml');
        $loader->load('security.yml');
        $loader->load('client.yml');
        $loader->load('data_update.yml');

        $this->cloneParameters(
            $container,
            self::CONFIG_PARAM_WEBSOCKET_DEFAULT_HOST,
            [
                self::CONFIG_PARAM_WEBSOCKET_BIND_ADDRESS,
                self::CONFIG_PARAM_WEBSOCKET_BACKEND_HOST,
                self::CONFIG_PARAM_WEBSOCKET_FRONTEND_HOST
            ]
        );
        $this->cloneParameters(
            $container,
            self::CONFIG_PARAM_WEBSOCKET_DEFAULT_PORT,
            [
                self::CONFIG_PARAM_WEBSOCKET_BIND_PORT,
                self::CONFIG_PARAM_WEBSOCKET_BACKEND_PORT,
                self::CONFIG_PARAM_WEBSOCKET_FRONTEND_PORT
            ]
        );

        $this->cloneParameters(
            $container,
            self::CONFIG_PARAM_WEBSOCKET_DEFAULT_PATH,
            [
                self::CONFIG_PARAM_WEBSOCKET_BACKEND_PATH,
                self::CONFIG_PARAM_WEBSOCKET_FRONTEND_PATH
            ]
        );

        if (isset($bundles['MonologBundle'])) {
            $this->configureLogger($container);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string $source
     * @param array $targets
     */
    private function cloneParameters(ContainerBuilder $container, $source, $targets): void
    {
        if ($container->hasParameter($source)) {
            $value = $container->getParameter($source);
            foreach ($targets as $target) {
                if (!$container->hasParameter($target)) {
                    $container->setParameter($target, $value);
                }
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function configureLogger(ContainerBuilder $container): void
    {
        if (true === $container->getParameter('kernel.debug')) {
            $verbosityLevels = [
                'VERBOSITY_NORMAL' => Logger::INFO,
                'VERBOSITY_VERBOSE' => Logger::DEBUG,
            ];
        } else {
            $verbosityLevels = [
                'VERBOSITY_NORMAL' => Logger::WARNING,
                'VERBOSITY_VERBOSE' => Logger::NOTICE,
                'VERBOSITY_VERY_VERBOSE' => Logger::INFO,
                'VERBOSITY_DEBUG' => Logger::DEBUG,
            ];
        }

        $monologConfig = [
            'channels' => ['oro_websocket'],
            'handlers' => [
                'websocket' => [
                    'type' => 'console',
                    'verbosity_levels' => $verbosityLevels,
                    'channels' => [
                        'type' => 'inclusive',
                        'elements' => ['oro_websocket'],
                    ],
                ],
            ],
        ];

        $container->prependExtensionConfig('monolog', $monologConfig);
    }
}
