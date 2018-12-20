<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all data transformers that are used to convert complex data types to scalars.
 */
class DataTransformerCompilerPass implements CompilerPassInterface
{
    private const DATA_TRANSFORMER_REGISTRY_SERVICE_ID = 'oro_api.data_transformer_registry';
    private const DATA_TRANSFORMER_TAG                 = 'oro.api.data_transformer';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $transformers = [];
        $taggedServices = $container->findTaggedServiceIds(self::DATA_TRANSFORMER_TAG);
        foreach ($taggedServices as $id => $attributes) {
            foreach ($attributes as $tagAttributes) {
                $transformers[DependencyInjectionUtil::getPriority($tagAttributes)][] = [
                    new Reference($id),
                    $tagAttributes['dataType'],
                    DependencyInjectionUtil::getRequestType($tagAttributes)
                ];
            }
        }
        if (empty($transformers)) {
            return;
        }

        $transformers = DependencyInjectionUtil::sortByPriorityAndFlatten($transformers);

        $groupedTransformers = [];
        foreach ($transformers as list($transformer, $dataType, $requestType)) {
            $groupedTransformers[$dataType][] = [$transformer, $requestType];
        }
        $container->getDefinition(self::DATA_TRANSFORMER_REGISTRY_SERVICE_ID)
            ->replaceArgument(0, $groupedTransformers);
    }
}
