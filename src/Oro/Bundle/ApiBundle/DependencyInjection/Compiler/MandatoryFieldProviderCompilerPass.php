<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
        $providers = [];
        $taggedServices = $container->findTaggedServiceIds(self::PROVIDER_TAG);
        foreach ($taggedServices as $id => $attributes) {
            $container->getDefinition($id)->setPublic(true);
            foreach ($attributes as $tagAttributes) {
                $providers[] = [
                    $id,
                    DependencyInjectionUtil::getRequestType($tagAttributes)
                ];
            }
        }
        if (empty($providers)) {
            return;
        }

        $container->getDefinition(self::PROVIDER_REGISTRY_SERVICE_ID)
            ->replaceArgument(0, $providers);
    }
}
