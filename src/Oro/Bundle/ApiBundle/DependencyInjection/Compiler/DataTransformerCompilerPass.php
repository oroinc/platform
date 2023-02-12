<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all data transformers that are used to convert complex data types to scalars.
 */
class DataTransformerCompilerPass implements CompilerPassInterface
{
    use ApiTaggedServiceTrait;

    private const DATA_TRANSFORMER_REGISTRY_SERVICE_ID = 'oro_api.data_transformer_registry';
    private const DATA_TRANSFORMER_TAG = 'oro.api.data_transformer';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $services = [];
        $transformers = [];
        $taggedServices = $container->findTaggedServiceIds(self::DATA_TRANSFORMER_TAG);
        foreach ($taggedServices as $id => $tags) {
            $services[$id] = new Reference($id);
            foreach ($tags as $attributes) {
                $transformers[$this->getPriorityAttribute($attributes)][] = [
                    $id,
                    $attributes['dataType'],
                    $this->getRequestTypeAttribute($attributes)
                ];
            }
        }

        $groupedTransformers = [];
        if ($transformers) {
            $transformers = $this->sortByPriorityAndFlatten($transformers);
            foreach ($transformers as [$id, $dataType, $requestType]) {
                $groupedTransformers[$dataType][] = [$id, $requestType];
            }
        }

        $container->getDefinition(self::DATA_TRANSFORMER_REGISTRY_SERVICE_ID)
            ->replaceArgument(0, $groupedTransformers)
            ->replaceArgument(1, ServiceLocatorTagPass::register($container, $services));
    }
}
