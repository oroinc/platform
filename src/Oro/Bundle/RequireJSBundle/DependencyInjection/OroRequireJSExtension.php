<?php

namespace Oro\Bundle\RequireJSBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class OroRequireJSExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter('oro_require_js', $config);
        $container->setParameter('oro_require_js.web_root', $config['web_root']);
        $container->setParameter('oro_require_js.build_path', $config['build_path']);

        if (!$container->hasDefinition('oro.cache.abstract') && $container->hasDefinition('oro_requirejs.cache')) {
            $oldDefinition = $container->getDefinition('oro_requirejs.cache');

            $definition = new Definition('Doctrine\\Common\\Cache\\PhpFileCache', array('%kernel.cache_dir%/oro_data'));
            $definition->setMethodCalls($oldDefinition->getMethodCalls());
            $definition->setPublic(false);

            $container->setDefinition('oro_requirejs.cache', $definition);
        }
    }
}
