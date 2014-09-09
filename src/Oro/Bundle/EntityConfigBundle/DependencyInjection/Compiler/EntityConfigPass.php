<?php

namespace Oro\Bundle\EntityConfigBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class EntityConfigPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $providerBagDefinition = $container->getDefinition('oro_entity_config.provider_bag');

        $configLoader = new CumulativeConfigLoader(
            'oro_entity_config',
            new YamlCumulativeFileLoader('Resources/config/entity_config.yml')
        );

        $resources = $configLoader->load($container);
        $scopes    = [];
        foreach ($resources as $resource) {
            if (!empty($resource->data['oro_entity_config'])) {
                foreach ($resource->data['oro_entity_config'] as $scope => $config) {
                    if (!empty($scopes[$scope])) {
                        $scopes[$scope] = array_merge_recursive($scopes[$scope], $config);
                    } else {
                        $scopes[$scope] = $config;
                    }
                }
            }
        }

        foreach ($scopes as $scope => $config) {
            $provider = new Definition('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider');
            $provider->setArguments(
                [
                    new Reference('oro_entity_config.config_manager'),
                    new Reference('service_container'),
                    $scope,
                    $config
                ]
            );

            $container->setDefinition('oro_entity_config.provider.' . $scope, $provider);

            $providerBagDefinition->addMethodCall('addProvider', array($provider));
        }
    }
}
