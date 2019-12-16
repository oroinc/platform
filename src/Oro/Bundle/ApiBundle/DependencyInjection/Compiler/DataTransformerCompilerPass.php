<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
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
        $services = [];
        $transformers = [];
        $taggedServices = $container->findTaggedServiceIds(self::DATA_TRANSFORMER_TAG);
        foreach ($taggedServices as $id => $attributes) {
            $services[$id] = new Reference($id);
            foreach ($attributes as $tagAttributes) {
                $transformers[DependencyInjectionUtil::getPriority($tagAttributes)][] = [
                    $id,
                    $tagAttributes['dataType'],
                    DependencyInjectionUtil::getRequestType($tagAttributes)
                ];
            }
        }

        $groupedTransformers = [];
        if ($transformers) {
            $transformers = DependencyInjectionUtil::sortByPriorityAndFlatten($transformers);
            foreach ($transformers as list($id, $dataType, $requestType)) {
                $groupedTransformers[$dataType][] = [$id, $requestType];
            }
        }

        $container->getDefinition(self::DATA_TRANSFORMER_REGISTRY_SERVICE_ID)
            ->replaceArgument(0, $groupedTransformers)
            ->replaceArgument(1, ServiceLocatorTagPass::register($container, $services));
    }
}
