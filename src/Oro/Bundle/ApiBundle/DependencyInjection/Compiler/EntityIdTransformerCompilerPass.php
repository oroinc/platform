<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all entity identifier transformers.
 */
class EntityIdTransformerCompilerPass implements CompilerPassInterface
{
    private const TRANSFORMER_REGISTRY_SERVICE_ID = 'oro_api.entity_id_transformer_registry';
    private const TRANSFORMER_TAG                 = 'oro.api.entity_id_transformer';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $services = [];
        $transformers = [];
        $taggedServices = $container->findTaggedServiceIds(self::TRANSFORMER_TAG);
        foreach ($taggedServices as $id => $attributes) {
            $services[$id] = new Reference($id);
            foreach ($attributes as $tagAttributes) {
                $transformers[DependencyInjectionUtil::getPriority($tagAttributes)][] = [
                    $id,
                    DependencyInjectionUtil::getRequestType($tagAttributes)
                ];
            }
        }

        if ($transformers) {
            $transformers = DependencyInjectionUtil::sortByPriorityAndFlatten($transformers);
        }

        $container->getDefinition(self::TRANSFORMER_REGISTRY_SERVICE_ID)
            ->setArgument(0, $transformers)
            ->setArgument(1, ServiceLocatorTagPass::register($container, $services));
    }
}
