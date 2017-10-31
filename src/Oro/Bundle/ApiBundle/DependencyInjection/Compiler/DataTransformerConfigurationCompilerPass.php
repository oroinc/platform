<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;

/**
 * Registers services tagged by "oro.api.data_transformer" tag.
 */
class DataTransformerConfigurationCompilerPass implements CompilerPassInterface
{
    const DATA_TRANSFORMER_REGISTRY_SERVICE_ID = 'oro_api.data_transformer_registry';
    const DATA_TRANSFORMER_TAG                 = 'oro.api.data_transformer';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // find data transformers
        $transformers = [];
        $taggedServices = $container->findTaggedServiceIds(self::DATA_TRANSFORMER_TAG);
        foreach ($taggedServices as $id => $attributes) {
            foreach ($attributes as $tagAttributes) {
                $transformers[DependencyInjectionUtil::getPriority($tagAttributes)][] = [
                    new Reference($id),
                    $tagAttributes['dataType'],
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
        $groupedTransformers = [];
        foreach ($transformers as list($transformer, $dataType, $requestType)) {
            $groupedTransformers[$dataType][] = [$transformer, $requestType];
        }
        $container->getDefinition(self::DATA_TRANSFORMER_REGISTRY_SERVICE_ID)
            ->replaceArgument(0, $groupedTransformers);
    }
}
