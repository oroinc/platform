<?php

namespace Oro\Bundle\FeatureToggleBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers extensions for features configuration
 * that is loaded from "Resources/config/oro/features.yml" files.
 */
class ConfigurationPass implements CompilerPassInterface
{
    private const CONFIGURATION_SERVICE = 'oro_featuretoggle.configuration';
    private const EXTENSION_TAG = 'oro_feature.config_extension';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $configurationDefinition = $container->getDefinition(self::CONFIGURATION_SERVICE);
        foreach ($container->findTaggedServiceIds(self::EXTENSION_TAG) as $id => $attributes) {
            $configurationDefinition->addMethodCall('addExtension', [new Reference($id)]);
        }
    }
}
