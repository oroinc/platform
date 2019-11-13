<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all providers of mandatory fields.
 */
class MandatoryFieldProviderCompilerPass implements CompilerPassInterface
{
    private const PROVIDER_REGISTRY_SERVICE_ID = 'oro_api.entity_serializer.mandatory_field_provider_registry';
    private const PROVIDER_TAG                 = 'oro.api.mandatory_field_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $services = [];
        $providers = [];
        $taggedServices = $container->findTaggedServiceIds(self::PROVIDER_TAG);
        foreach ($taggedServices as $id => $attributes) {
            $services[$id] = new Reference($id);
            foreach ($attributes as $tagAttributes) {
                $providers[] = [
                    $id,
                    DependencyInjectionUtil::getRequestType($tagAttributes)
                ];
            }
        }

        $container->getDefinition(self::PROVIDER_REGISTRY_SERVICE_ID)
            ->setArgument(0, $providers)
            ->setArgument(1, ServiceLocatorTagPass::register($container, $services));
    }
}
