<?php

namespace Oro\Bundle\FeatureToggleBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Injects a feature name and the feature checker service
 * into services marked by the "oro_featuretogle.feature" tag.
 */
class FeatureToggleablePass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $services = [];
        foreach ($container->findTaggedServiceIds('oro_featuretogle.feature') as $id => $attributes) {
            $featureName = $attributes[0]['feature'] ?? null;
            if ($featureName) {
                $container->getDefinition($id)
                    ->addMethodCall('addFeature', [$featureName]);
            }
            $services[$id] = true;
        }
        $checkerReference = new Reference('oro_featuretoggle.checker.feature_checker');
        foreach ($services as $serviceId => $val) {
            $container->getDefinition($serviceId)
                ->addMethodCall('setFeatureChecker', [$checkerReference]);
        }
    }
}
