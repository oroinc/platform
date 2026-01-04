<?php

namespace Oro\Bundle\EntityConfigBundle\DependencyInjection\Compiler;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderBag;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigBag;
use Oro\Component\Config\Loader\ContainerBuilderAdapter;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\FolderYamlCumulativeFileLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configures entity config manager and providers based on the configuration loaded
 * from "Resources/config/oro/entity_config.yml".
 */
class EntityConfigPass implements CompilerPassInterface
{
    public const CONFIG_MANAGER_SERVICE         = 'oro_entity_config.config_manager';
    public const CONFIG_BAG_SERVICE             = 'oro_entity_config.provider_config_bag';
    public const CONFIG_PROVIDER_BAG_SERVICE    = 'oro_entity_config.provider_bag';
    public const CONFIG_PROVIDER_SERVICE_PREFIX = 'oro_entity_config.provider.';
    public const CONFIG_CACHE_SERVICE           = 'oro_entity_config.cache';
    public const CONFIG_HANDLER_SERVICE         = 'oro_entity_config.configuration_handler';

    public const ENTITY_CONFIG_ROOT_NODE = 'entity_config';

    private const CONFIG_FILE = 'Resources/config/oro/entity_config.yml';

    private const APP_CONFIG_PATH = '../config/oro/entity';

    #[\Override]
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

        // inject the config provider bag into the configuration handler/validator
        $configHandler = $container->getDefinition(self::CONFIG_HANDLER_SERVICE);
        $configHandler->addMethodCall('setProviderBag', [$providerBagRef]);

        // register the config providers for all scopes
        foreach ($scopes as $scope) {
            $provider = new Definition(ConfigProvider::class, [$scope]);
            $provider->setFactory([$providerBagRef, 'getProvider']);
            $container->setDefinition(self::CONFIG_PROVIDER_SERVICE_PREFIX . $scope, $provider)
                ->setPublic(true);
        }

        // add scopes to the config cache
        $configCache = $container->getDefinition(self::CONFIG_CACHE_SERVICE);
        $configCache->replaceArgument(2, array_combine($scopes, $scopes));
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
            [
                new YamlCumulativeFileLoader(self::CONFIG_FILE),
                new FolderYamlCumulativeFileLoader($this->getAppConfigPath()),
            ]
        );
        $result = [];
        $resources = $configLoader->load(new ContainerBuilderAdapter($container));
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

    protected function getAppConfigPath(): string
    {
        return self::APP_CONFIG_PATH;
    }
}
