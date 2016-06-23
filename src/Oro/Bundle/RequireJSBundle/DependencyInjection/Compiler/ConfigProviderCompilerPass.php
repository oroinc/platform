<?php

namespace Oro\Bundle\RequireJSBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class ConfigProviderCompilerPass implements CompilerPassInterface
{
    const PROVIDER_SERVICE  = 'oro_requirejs.config_provider.chain';
    const TAG_NAME          = 'requirejs.config_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::PROVIDER_SERVICE)) {
            $chainDef = $container->getDefinition(self::PROVIDER_SERVICE);

            foreach ($container->findTaggedServiceIds(self::TAG_NAME) as $serviceId => $tag) {
                $chainDef->addMethodCall('addProvider', [new Reference($serviceId)]);
            }
        }
    }
}