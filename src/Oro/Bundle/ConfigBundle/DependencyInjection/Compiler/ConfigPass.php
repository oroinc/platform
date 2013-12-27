<?php

namespace Oro\Bundle\ConfigBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Extension\Extension;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class ConfigPass implements CompilerPassInterface
{
    const CONFIG_DEFINITION_BAG_SERVICE = 'oro_config.config_definition_bag';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $settings = [];

        /** @var Extension $extension */
        foreach ($container->getExtensions() as $name => $extension) {
            $config = $container->getExtensionConfig($name);
            // take last merged configuration from sub-container
            $config = end($config);
            if (!$config) {
                continue;
            }

            if (isset($config['settings'])) {
                if (empty($config['settings'][SettingsBuilder::RESOLVED_KEY])) {
                    throw new \LogicException('Direct passed "settings" are not allowed');
                }

                $settings[$name] = $config['settings'];
            }
        }

        $container->getDefinition(self::CONFIG_DEFINITION_BAG_SERVICE)->replaceArgument(0, $settings);
    }
}
