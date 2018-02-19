<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;

/**
 * Registers all entity identifir transformers.
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
        // find entity id transformers
        $transformers = [];
        $taggedServices = $container->findTaggedServiceIds(self::TRANSFORMER_TAG);
        foreach ($taggedServices as $id => $attributes) {
            foreach ($attributes as $tagAttributes) {
                $transformers[DependencyInjectionUtil::getPriority($tagAttributes)][] = [
                    new Reference($id),
                    DependencyInjectionUtil::getAttribute($tagAttributes, 'requestType', null)
                ];
            }
        }
        if (empty($transformers)) {
            return;
        }

        // sort by priority and flatten
        $transformers = DependencyInjectionUtil::sortByPriorityAndFlatten($transformers);

        // register
        $container->getDefinition(self::TRANSFORMER_REGISTRY_SERVICE_ID)
            ->replaceArgument(0, $transformers);
    }
}
