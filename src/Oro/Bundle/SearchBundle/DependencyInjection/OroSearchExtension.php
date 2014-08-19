<?php

namespace Oro\Bundle\SearchBundle\DependencyInjection;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class OroSearchExtension extends Extension
{
    /**
     * Load configuration
     *
     * @param  array            $configs
     * @param  ContainerBuilder $container
     * @throws InvalidConfigurationException
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $alias  = $this->getAlias();
        $config = $this->processConfiguration(new Configuration(), $configs);
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $ymlLoader    = new YamlCumulativeFileLoader('Resources/config/oro/engine/' . $config['engine'] . '.yml');
        $configLoader = new CumulativeConfigLoader($alias, $ymlLoader);
        $resources    = $configLoader->load($container);

        if (!empty($resources)) {
            $resource = end($resources);
            $loader->load($resource->path);
        }

        $loader->load('services.yml');
        $this->searchMappingsConfig('entities_config', 'search', $config, $container);
        $this->searchMappingsConfig('engine_config', 'search_engine', $config, $container);

        if (Configuration::DEFAULT_ENGINE == $config['engine']) {
            $driversAlias = $alias . '.drivers';

            if ($container->hasParameter($driversAlias)) {
                $config['drivers'] = $container->getParameter($driversAlias);
            } else {
                throw new InvalidConfigurationException(sprintf('"%s" are required for ORM engine', $driversAlias));
            }

            $this->remapParameters($config, $container, array('drivers' => $driversAlias));
        }

        $container->setParameter($alias . '.engine', $config['engine']);
        $container->setParameter($alias . '.log_queries', $config['log_queries']);
        $container->setParameter($alias . '.realtime_update', $config['realtime_update']);
        $container->setParameter($alias . '.twig.item_container_template', $config['item_container_template']);
    }

    /**
     * Get alias
     *
     * @return string
     */
    public function getAlias()
    {
        return 'oro_search';
    }

    /**
     * Add search mapping config
     *
     * @param string           $configKey
     * @param string           $fileName
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function searchMappingsConfig($configKey, $fileName, $config, ContainerBuilder $container)
    {
        $alias        = $this->getAlias();
        $configPart   = empty($config[$configKey]) ? array() : $config[$configKey];
        $ymlLoader    = new YamlCumulativeFileLoader('Resources/config/' . $fileName . '.yml');
        $configLoader = new CumulativeConfigLoader($alias, $ymlLoader);
        $resources    = $configLoader->load($container);

        foreach ($resources as $resource) {
            $configPart += $resource->data;
        }

        $container->setParameter($alias . '.' . $configKey, $configPart);
    }

    /**
     * Remap parameters form to container params
     *
     * @param array            $config
     * @param ContainerBuilder $container
     * @param array            $map
     */
    protected function remapParameters(array $config, ContainerBuilder $container, array $map)
    {
        foreach ($map as $name => $paramName) {
            if (array_key_exists($name, $config)) {
                $container->setParameter($paramName, $config[$name]);
            }
        }
    }
}
