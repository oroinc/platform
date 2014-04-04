<?php

namespace Oro\Bundle\EntityConfigBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\CacheBundle\Config\CumulativeResourceManager;

class EntityConfigPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $providerBagDefinition = $container->getDefinition('oro_entity_config.provider_bag');

        $resources = CumulativeResourceManager::getInstance()
            ->getLoader('OroEntityConfigBundle')
            ->load($container);
        foreach ($resources as $resource) {
            if (isset($resource->data['oro_entity_config']) && count($resource->data['oro_entity_config'])) {
                foreach ($resource->data['oro_entity_config'] as $scope => $config) {
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
    }
}
