<?php

namespace Oro\Bundle\SyncBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class OroSyncExtension extends Extension
{
    const CONFIG_PARAM_WEBSOCKET_DEFAULT_HOST  = 'websocket_host';
    const CONFIG_PARAM_WEBSOCKET_DEFAULT_PORT  = 'websocket_port';
    const CONFIG_PARAM_WEBSOCKET_BIND_ADDRESS  = 'websocket_bind_address';
    const CONFIG_PARAM_WEBSOCKET_BIND_PORT     = 'websocket_bind_port';
    const CONFIG_PARAM_WEBSOCKET_BACKEND_HOST  = 'websocket_backend_host';
    const CONFIG_PARAM_WEBSOCKET_BACKEND_PORT  = 'websocket_backend_port';
    const CONFIG_PARAM_WEBSOCKET_FRONTEND_HOST = 'websocket_frontend_host';
    const CONFIG_PARAM_WEBSOCKET_FRONTEND_PORT = 'websocket_frontend_port';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('services.yml');

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
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $source
     * @param array            $targets
     */
    protected function cloneParameters(ContainerBuilder $container, $source, $targets)
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
}
