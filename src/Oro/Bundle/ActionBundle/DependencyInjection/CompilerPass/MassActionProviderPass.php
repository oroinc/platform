<?php

namespace Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MassActionProviderPass implements CompilerPassInterface
{
    const PROVIDER_TAG = 'oro_action.datagrid.mass_action_provider';
    const REGISTRY_SERVICE_ID = 'oro_action.datagrid.mass_action_provider.registry';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::REGISTRY_SERVICE_ID)) {
            return;
        }

        $providers = $container->findTaggedServiceIds(self::PROVIDER_TAG);
        if (!$providers) {
            return;
        }

        $registry = $container->getDefinition(self::REGISTRY_SERVICE_ID);

        foreach ($providers as $id => $attributes) {
            $definition = $container->getDefinition($id);
            $definition->setPublic(false);

            foreach ($attributes as $eachTag) {
                $alias = empty($eachTag['alias']) ? $id : $eachTag['alias'];

                $registry->addMethodCall('addProvider', [$alias, $definition]);
            }
        }
    }
}
