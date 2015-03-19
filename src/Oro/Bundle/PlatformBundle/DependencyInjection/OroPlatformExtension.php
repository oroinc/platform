<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

use Oro\Bundle\DistributionBundle\DependencyInjection\OroContainerBuilder;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class OroPlatformExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        $configLoader = new CumulativeConfigLoader(
            'oro_app_config',
            new YamlCumulativeFileLoader('Resources/config/oro/app.yml')
        );
        $resources    = $configLoader->load();
        $extensions   = $container->getExtensions();
        foreach ($resources as $resource) {
            foreach ($resource->data as $name => $config) {
                if (!empty($extensions[$name])) {
                    if ($name === 'security') {
                        $this->mergeConfigIntoOne($container, $name, $config);
                    } else {
                        $container->prependExtensionConfig($name, $config);
                    }
                }
            }
        }
    }

    /**
     * Merge configuration into one config
     *
     * @param ContainerBuilder $container
     * @param string $name
     * @param array $config
     *
     * @throws \RuntimeException
     */
    private function mergeConfigIntoOne(ContainerBuilder $container, $name, array $config = [])
    {
        if (!$container instanceof OroContainerBuilder) {
            throw new \RuntimeException(sprintf('%s is expected to be passed into OroPlatformExtension',
                'Oro\Bundle\DistributionBundle\DependencyInjection\OroContainerBuilder'));
        }

        $originalConfig = $container->getExtensionConfig($name);
        if (!count($originalConfig)) {
            $originalConfig[] = array();
        }

        $mergedConfig = array_merge_recursive($originalConfig[0], $config);
        $originalConfig[0] = $mergedConfig;

        $container->setExtensionConfig('security', $originalConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $loader->load('services.yml');
    }
}
