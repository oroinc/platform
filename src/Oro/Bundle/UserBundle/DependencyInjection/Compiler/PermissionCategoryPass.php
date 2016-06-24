<?php

namespace Oro\Bundle\UserBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PermissionCategoryPass implements CompilerPassInterface
{
    const REGISTRY_SERVICE = 'oro_user.provider.role_permission_category_provider';
    const TAG = 'oro_user.permission_category';
    const PRIORITY = 'priority';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::REGISTRY_SERVICE)) {
            return;
        }
        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        if (count($taggedServices) === 0) {
            return;
        }

        $registryDefinition = $container->getDefinition(self::REGISTRY_SERVICE);
        foreach ($taggedServices as $serviceId => $tags) {
            $registryDefinition->addMethodCall('addProvider', [new Reference($serviceId)]);
        }
    }
}
