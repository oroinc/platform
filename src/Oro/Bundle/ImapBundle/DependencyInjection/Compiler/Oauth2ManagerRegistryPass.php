<?php

namespace Oro\Bundle\ImapBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass registering all OAuth 2 managers (implementation of
 * Oro\Bundle\ImapBundle\Manager\Oauth2ManagerInterface) into a registry
 * instance.
 */
class Oauth2ManagerRegistryPass implements CompilerPassInterface
{
    private const TAG_MANAGER = 'oro_imap.oauth2_manager';

    private const TAG_REGISTRY = 'oro_imap.manager_registry.registry';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        // Check if registry defined to apply tagged managers
        if ($container->hasDefinition(self::TAG_REGISTRY)) {
            $this->doProcess(
                $container->getDefinition(self::TAG_REGISTRY),
                $container->findTaggedServiceIds(self::TAG_MANAGER)
            );
        }
    }

    /**
     * @param Definition $registryDefinition
     * @param array $taggedServices
     */
    private function doProcess(Definition $registryDefinition, array $taggedServices): void
    {
        foreach (array_keys($taggedServices) as $loaderServiceId) {
            $registryDefinition->addMethodCall('addManager', [new Reference($loaderServiceId)]);
        }
    }
}
