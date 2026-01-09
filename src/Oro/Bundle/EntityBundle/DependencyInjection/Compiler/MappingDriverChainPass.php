<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Oro\Bundle\EntityBundle\ORM\MappingDriverChain;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Replaces the Doctrine MappingDriverChain with Oro's custom implementation
 */
class MappingDriverChainPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition('doctrine.orm.default_metadata_driver');
        $definition->setClass(MappingDriverChain::class);
    }
}
