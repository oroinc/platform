<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers all ACL group providers.
 */
class AclGroupProvidersPass implements CompilerPassInterface
{
    use PriorityTaggedLocatorTrait;

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $services = $this->findAndSortTaggedServices('oro_security.acl.group_provider', 'alias', $container);

        $container->getDefinition('oro_security.acl.group_provider.chain')
            ->setArgument('$providers', array_values($services));
    }
}
