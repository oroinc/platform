<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

class OroDataCacheManagerPass implements CompilerPassInterface
{
    const MANAGER_SERVICE_KEY        = 'oro.oro_data_cache_manager';
    const ABSTRACT_CACHE_SERVICE_KEY = 'oro.cache.abstract';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::MANAGER_SERVICE_KEY)) {
            return;
        }
        if (!$container->hasDefinition(self::ABSTRACT_CACHE_SERVICE_KEY)) {
            return;
        }

        $managerDef  = $container->getDefinition(self::MANAGER_SERVICE_KEY);
        $definitions = $container->getDefinitions();
        foreach ($definitions as $serviceId => $def) {
            if ($def instanceof DefinitionDecorator
                && !$def->isAbstract()
                && $def->getParent() === self::ABSTRACT_CACHE_SERVICE_KEY
            ) {
                $managerDef->addMethodCall(
                    'registerCacheProvider',
                    [new Reference($serviceId)]
                );
            }
        }
    }
}
