<?php

namespace Oro\Bundle\FeatureToggleBundle\DependencyInjection\CompilerPass;

use Oro\Bundle\FeatureToggleBundle\Configuration\FeatureToggleConfiguration;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ConfigurationPass implements CompilerPassInterface
{
    const CACHE = 'oro_featuretoggle.cache.provider.features';
    const PROVIDER = 'oro_featuretoggle.configuration.provider';
    const CONFIG_FILE_PATH = 'Resources/config/oro/features.yml';

    const CONFIGURATION_SERVICE = 'oro_featuretoggle.configuration';
    const EXTENSION_TAG = 'oro_feature.config_extension';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->loadExtensions($container);
        $this->loadConfigurations($container);
        $this->clearCache($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function loadExtensions(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::CONFIGURATION_SERVICE)) {
            $configurationDefinition = $container->getDefinition(self::CONFIGURATION_SERVICE);
            foreach ($container->findTaggedServiceIds(self::EXTENSION_TAG) as $id => $attributes) {
                $configurationDefinition->addMethodCall('addExtension', [new Reference($id)]);
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function loadConfigurations(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::PROVIDER)) {
            $rawConfiguration = [];

            $loader = new CumulativeConfigLoader(
                'features',
                new YamlCumulativeFileLoader(self::CONFIG_FILE_PATH)
            );
            $resources = $loader->load($container);
            $nodeName = FeatureToggleConfiguration::ROOT;
            foreach ($resources as $resource) {
                if (array_key_exists($nodeName, (array)$resource->data) && is_array($resource->data[$nodeName])) {
                    $rawConfiguration[$resource->bundleClass] = $resource->data[$nodeName];
                }
            }

            $providerDef = $container->getDefinition(self::PROVIDER);
            $providerDef->replaceArgument(0, $rawConfiguration);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function clearCache(ContainerBuilder $container)
    {
        if ($container->has(self::CACHE)) {
            $container->get(self::CACHE)->deleteAll();
        }
    }
}
