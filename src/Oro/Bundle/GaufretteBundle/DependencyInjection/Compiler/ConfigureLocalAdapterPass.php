<?php

namespace Oro\Bundle\GaufretteBundle\DependencyInjection\Compiler;

use Oro\Bundle\GaufretteBundle\Adapter\LocalAdapter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Substitutes a local Gaufrette adapter with an optimized version of the local adapter.
 */
class ConfigureLocalAdapterPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $container->getDefinition('knp_gaufrette.adapter.local')
            ->setClass(LocalAdapter::class);
    }
}
