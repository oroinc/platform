<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Oro\Bundle\EntityBundle\DependencyInjection\OroEntityExtension;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Configures the default lifetime of cached ORM queries.
 */
class ManagerRegistryCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('doctrine')
            ->addMethodCall(
                'setDefaultQueryCacheLifetime',
                [$container->getParameter(OroEntityExtension::DEFAULT_QUERY_CACHE_LIFETIME_PARAM_NAME)]
            );
        $container->getParameterBag()->remove(OroEntityExtension::DEFAULT_QUERY_CACHE_LIFETIME_PARAM_NAME);
    }
}
