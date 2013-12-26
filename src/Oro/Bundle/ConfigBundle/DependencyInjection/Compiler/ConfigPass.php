<?php

namespace Oro\Bundle\ConfigBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

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

        foreach ($container->getExtensions() as $name => $extension) {
            if (!$config = $extension->getConfiguration([], $container)) {
                continue;
            }

            $config = $processor->processConfiguration(
                $config,
                $container->getExtensionConfig($name)
            );

            if (isset($config['settings'])) {
                $settings[$name] = $config['settings'];
            }
        }

        $container->getDefinition(self::CONFIG_DEFINITION_BAG_SERVICE)->replaceArgument(0, $settings);
    }
}
