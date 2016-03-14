<?php

namespace Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class ConfigurationPass implements CompilerPassInterface
{
    const CACHE_SERVICE_ID = 'oro_action.cache.provider';
    const PROVIDER_SERVICE_ID = 'oro_action.configuration.provider';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerConfigFiles($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function registerConfigFiles(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::PROVIDER_SERVICE_ID)) {
            $configLoader = new CumulativeConfigLoader(
                'oro_action',
                new YamlCumulativeFileLoader('Resources/config/actions.yml')
            );

            $config = [];

            $resources = $configLoader->load($container);
            foreach ($resources as $resource) {
                if (array_key_exists(ActionConfigurationProvider::ROOT_NODE_NAME, (array)$resource->data) &&
                    is_array($resource->data[ActionConfigurationProvider::ROOT_NODE_NAME])
                ) {
                    $config[$resource->bundleClass] = $resource->data[ActionConfigurationProvider::ROOT_NODE_NAME];
                }
            }

            $providerDef = $container->getDefinition(self::PROVIDER_SERVICE_ID);
            $providerDef->replaceArgument(3, $config);
        }

        $container->get(self::CACHE_SERVICE_ID)->deleteAll();
    }
}
