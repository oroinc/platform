<?php

namespace Oro\Bundle\FeatureToggleBundle\DependencyInjection\CompilerPass;

use Oro\Bundle\FeatureToggleBundle\Configuration\FeatureToggleConfiguration;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ConfigurationPass implements CompilerPassInterface
{
    const CACHE = 'oro_featuretoggle.cache.provider.features';
    const PROVIDER = 'oro_featuretoggle.configuration.provider';
    const CONFIG_FILE_PATH = 'Resources/config/oro/features.yml';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
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

        if ($container->has(self::CACHE)) {
            $container->get(self::CACHE)->deleteAll();
        }
    }
}
