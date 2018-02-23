<?php

namespace Oro\Bundle\EntityConfigBundle\DependencyInjection\Compiler;

use Oro\Bundle\EntityConfigBundle\Config\DebugConfigCache;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderBag;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigBag;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class EntityConfigPass implements CompilerPassInterface
{
    const CONFIG_MANAGER_SERVICE         = 'oro_entity_config.config_manager';
    const CONFIG_BAG_SERVICE             = 'oro_entity_config.provider_config_bag';
    const CONFIG_PROVIDER_BAG_SERVICE    = 'oro_entity_config.provider_bag';
    const CONFIG_PROVIDER_SERVICE_PREFIX = 'oro_entity_config.provider.';
    const CONFIG_CACHE_SERVICE           = 'oro_entity_config.cache';

    const ENTITY_CONFIG_ROOT_NODE = 'entity_config';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $configManager = $container->getDefinition(self::CONFIG_MANAGER_SERVICE);
        $config = $this->loadConfig($container);
        $scopes = array_keys($config);

        // register the config bag service
        $configBag = new Definition(PropertyConfigBag::class, [$config]);
        $configBag->setPublic(false);
        $configBag->setLazy(true);
        $container->setDefinition(self::CONFIG_BAG_SERVICE, $configBag);

        // register the link to the config manager service
        $configManagerLinkServiceId = self::CONFIG_MANAGER_SERVICE . '.link';
        $configManagerLink = new Definition(
            ServiceLink::class,
            [new Reference('service_container'), self::CONFIG_MANAGER_SERVICE]
        );
        $configManagerLink->setPublic(false);
        $container->setDefinition($configManagerLinkServiceId, $configManagerLink);

        // register the config provider bag service
        $container->setDefinition(
            self::CONFIG_PROVIDER_BAG_SERVICE,
            new Definition(
                ConfigProviderBag::class,
                [$scopes, new Reference($configManagerLinkServiceId), new Reference(self::CONFIG_BAG_SERVICE)]
            )
        );

        // inject the config provider bag to the config manager
        $providerBagRef = new Reference(self::CONFIG_PROVIDER_BAG_SERVICE);
        $configManager->addMethodCall('setProviderBag', [$providerBagRef]);

        // register the config providers for all scopes
        foreach ($scopes as $scope) {
            $provider = new Definition(ConfigProvider::class, [$scope]);
            $provider->setFactory([$providerBagRef, 'getProvider']);
            $container->setDefinition(self::CONFIG_PROVIDER_SERVICE_PREFIX . $scope, $provider);
        }

        // use a special implementation of the config cache in the debug mode
        if ($container->getParameter('kernel.debug')) {
            $container->getDefinition(self::CONFIG_CACHE_SERVICE)
                ->setClass(DebugConfigCache::class);
        }
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array [scope => scope config, ...]
     */
    private function loadConfig(ContainerBuilder $container)
    {
        $configLoader = new CumulativeConfigLoader(
            'oro_entity_config',
            new YamlCumulativeFileLoader('Resources/config/oro/entity_config.yml')
        );

        $result = [];
        $resources = $configLoader->load($container);
        foreach ($resources as $resource) {
            if (!empty($resource->data[self::ENTITY_CONFIG_ROOT_NODE])) {
                foreach ($resource->data[self::ENTITY_CONFIG_ROOT_NODE] as $scope => $config) {
                    if (!empty($result[$scope])) {
                        $result[$scope] = array_merge_recursive($result[$scope], $config);
                    } else {
                        $result[$scope] = $config;
                    }
                }
            }
        }

        return $result;
    }
}
