<?php

namespace Oro\Bundle\SyncBundle\DependencyInjection;

use Monolog\Logger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroSyncExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('security.yml');
        $loader->load('server.yml');
        $loader->load('client.yml');
        $loader->load('data_update.yml');
        $loader->load('controllers.yml');

        $this->configureLogger($container);
        if ('test' === $container->getParameter('kernel.environment')) {
            $loader->load('services_test.yml');
        }
    }

    private function configureLogger(ContainerBuilder $container): void
    {
        if (true === $container->getParameter('kernel.debug')) {
            $verbosityLevels = [
                'VERBOSITY_QUIET' => Logger::ERROR,
                'VERBOSITY_NORMAL' => Logger::INFO,
                'VERBOSITY_VERBOSE' => Logger::DEBUG,
                'VERBOSITY_VERY_VERBOSE' => Logger::DEBUG,
                'VERBOSITY_DEBUG' => Logger::DEBUG,
            ];
        } else {
            $verbosityLevels = [
                'VERBOSITY_QUIET' => Logger::ERROR,
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
                        'elements' => ['oro_websocket', 'websocket'],
                    ],
                    // Should be one of the first handlers to avoid cancelling of this handler by another
                    // handler with higher priority.
                    'priority' => 512,
                ],
            ],
        ];

        $container->prependExtensionConfig('monolog', $monologConfig);

        $container
            ->getDefinition('oro_sync.log.handler.websocket_server_console')
            // see Symfony\Bundle\MonologBundle\DependencyInjection\MonologExtension::getHandlerId
            ->setDecoratedService('monolog.handler.websocket');
    }
}
