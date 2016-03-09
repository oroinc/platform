<?php

namespace Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class ConfigurationPass implements CompilerPassInterface
{
    const OPERATIONS_CACHE = 'oro_action.cache.provider.operations';
    const OPERATIONS_PROVIDER = 'oro_action.configuration.provider.operations';
    const OPERATIONS_NODE_NAME = 'operations';

    const ACTION_GROUPS_CACHE = 'oro_action.cache.provider.action_groups';
    const ACTION_GROUPS_PROVIDER = 'oro_action.configuration.provider.action_groups';
    const ACTION_GROUPS_NODE_NAME = 'action_groups';

    const CONFIG_FILE_PATH = 'Resources/config/oro/actions.yml';

    /** @var CumulativeConfigLoader */
    protected $loader;

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerConfiguration(
            $container,
            self::OPERATIONS_PROVIDER,
            self::OPERATIONS_CACHE,
            self::OPERATIONS_NODE_NAME
        );
        $this->registerConfiguration(
            $container,
            self::ACTION_GROUPS_PROVIDER,
            self::ACTION_GROUPS_CACHE,
            self::ACTION_GROUPS_NODE_NAME
        );
    }

    /**
     * @param ContainerBuilder $container
     * @param string $configurationProvider
     * @param string $cache
     * @param string $nodeName
     */
    protected function registerConfiguration(ContainerBuilder $container, $configurationProvider, $cache, $nodeName)
    {
        if ($container->hasDefinition($configurationProvider)) {
            $config = [];

            $resources = $this->getLoader()->load($container);
            foreach ($resources as $resource) {
                if (array_key_exists($nodeName, (array)$resource->data) && is_array($resource->data[$nodeName])) {
                    $config[$resource->bundleClass] = $resource->data[$nodeName];
                }
            }

            $providerDef = $container->getDefinition($configurationProvider);
            $providerDef->replaceArgument(3, $config);
        }

        if ($container->has($cache)) {
            $container->get($cache)->deleteAll();
        }
    }

    /**
     * @return CumulativeConfigLoader
     */
    protected function getLoader()
    {
        if (!$this->loader) {
            $this->loader = new CumulativeConfigLoader(
                'oro_action',
                new YamlCumulativeFileLoader(self::CONFIG_FILE_PATH)
            );
        }

        return $this->loader;
    }
}
