<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all post processors that are used to post process field values.
 */
class PostProcessorCompilerPass implements CompilerPassInterface
{
    use ApiTaggedServiceTrait;

    private const POST_PROCESSOR_REGISTRY_SERVICE_ID = 'oro_api.post_processor_registry';
    private const POST_PROCESSOR_TAG = 'oro.api.post_processor';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $services = [];
        $transformers = [];
        $taggedServices = $container->findTaggedServiceIds(self::POST_PROCESSOR_TAG);
        foreach ($taggedServices as $id => $tags) {
            $services[$id] = new Reference($id);
            foreach ($tags as $attributes) {
                $transformers[$this->getPriorityAttribute($attributes)][] = [
                    $id,
                    $attributes['alias'],
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

        $container->getDefinition(self::POST_PROCESSOR_REGISTRY_SERVICE_ID)
            ->replaceArgument(0, $groupedTransformers)
            ->replaceArgument(1, ServiceLocatorTagPass::register($container, $services));
    }
}
