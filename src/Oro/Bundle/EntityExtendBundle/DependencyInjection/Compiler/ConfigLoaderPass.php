<?php

namespace Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ConfigLoaderPass implements CompilerPassInterface
{
    const CONFIG_LOADER_CLASS_PARAM = 'oro_entity_config.config_loader.class';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasParameter(self::CONFIG_LOADER_CLASS_PARAM)) {
            $container->setParameter(
                self::CONFIG_LOADER_CLASS_PARAM,
                'Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigLoader'
            );
        }
    }
}
