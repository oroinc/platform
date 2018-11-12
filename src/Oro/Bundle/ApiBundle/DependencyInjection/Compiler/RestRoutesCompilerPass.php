<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all routes providers for REST based APIs.
 */
class RestRoutesCompilerPass implements CompilerPassInterface
{
    private const REGISTRY_SERVICE_ID = 'oro_api.rest.routes_registry';
    private const PROVIDER_TAG        = 'oro.api.rest_routes';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $providers = [];
        $taggedServices = $container->findTaggedServiceIds(self::PROVIDER_TAG);
        foreach ($taggedServices as $id => $attributes) {
            foreach ($attributes as $tagAttributes) {
                $providers[DependencyInjectionUtil::getPriority($tagAttributes)][] = [
                    new Reference($id),
                    DependencyInjectionUtil::getRequestType($tagAttributes)
                ];
            }
        }
        if (empty($providers)) {
            return;
        }

        $providers = DependencyInjectionUtil::sortByPriorityAndFlatten($providers);

        $container->getDefinition(self::REGISTRY_SERVICE_ID)
            ->replaceArgument(0, $providers);
    }
}
