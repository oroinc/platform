<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class VirtualFieldConfigurationPass implements CompilerPassInterface
{
    const CHAIN_PROVIDER_SERVICE_ID = 'oro_entity.virtual_field_provider';
    const PROVIDER_TAG_NAME         = 'oro_entity.virtual_field_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerConfigProviders($container);
    }

    /**
     * Register all datagrid configuration providers
     *
     * @param ContainerBuilder $container
     */
    protected function registerConfigProviders(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::CHAIN_PROVIDER_SERVICE_ID)) {
            $providers = array();
            foreach ($container->findTaggedServiceIds(self::PROVIDER_TAG_NAME) as $id => $attributes) {
                $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
                $providers[$priority][] = new Reference($id);
            }
            if (!empty($providers)) {
                // sort by priority and flatten
                krsort($providers);
                $providers = call_user_func_array('array_merge', $providers);
                // add to chain provider
                $chainConfigProviderDef = $container->getDefinition(self::CHAIN_PROVIDER_SERVICE_ID);
                foreach ($providers as $provider) {
                    $chainConfigProviderDef->addMethodCall('addProvider', [$provider]);
                }
            }
        }
    }
}
