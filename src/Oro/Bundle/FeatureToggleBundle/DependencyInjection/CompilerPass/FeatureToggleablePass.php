<?php

namespace Oro\Bundle\FeatureToggleBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FeatureToggleablePass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oro_featuretoggle.checker.feature_checker')) {
            return;
        }

        $services = [];
        foreach ($container->findTaggedServiceIds('oro_featuretogle.feature') as $id => $attributes) {
            $featureName = isset($attributes[0]['feature']) ? $attributes[0]['feature'] : null;
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
