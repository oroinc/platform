<?php

namespace Oro\Bundle\ConfigBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Extension\Extension;

class ConfigPass implements CompilerPassInterface
{
    const CONFIG_DEFINITION_BAG_SERVICE = 'oro_config.config_definition_bag';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $processor = new Processor();
        $settings  = [];

        /** @var Extension $extension */
        foreach ($container->getExtensions() as $name => $extension) {
            $configurationTree = $extension->getConfiguration([], $container);
            $config            = $container->getExtensionConfig($name);
            if (!($config && $configurationTree instanceof ConfigurationInterface)) {
                // this extension was not called
                continue;
            }
            $config = $container->getParameterBag()->resolveValue($config);
            $config = $processor->processConfiguration($configurationTree, $config);

            if (isset($config['settings'])) {
                $settings[$name] = $config['settings'];
            }
        }

        $container->getDefinition(self::CONFIG_DEFINITION_BAG_SERVICE)->replaceArgument(0, $settings);
    }
}
