<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers document builders for all supported API request types.
 */
class DocumentBuilderCompilerPass implements CompilerPassInterface
{
    use ApiTaggedServiceTrait;

    private const DOCUMENT_BUILDER_FACTORY_SERVICE_ID = 'oro_api.document_builder_factory';
    private const DOCUMENT_BUILDER_TAG = 'oro.api.document_builder';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $services = [];
        $documentBuilders = [];
        $taggedServices = $container->findTaggedServiceIds(self::DOCUMENT_BUILDER_TAG);
        foreach ($taggedServices as $id => $tags) {
            if ($container->getDefinition($id)->isShared()) {
                throw new LogicException(sprintf('The document builder service "%s" should be non shared.', $id));
            }
            $services[$id] = new Reference($id);
            foreach ($tags as $attributes) {
                $documentBuilders[$this->getPriorityAttribute($attributes)][] = [
                    $id,
                    $this->getRequestTypeAttribute($attributes)
                ];
            }
        }
        if ($documentBuilders) {
            $documentBuilders = $this->sortByPriorityAndFlatten($documentBuilders);
        }

        $container->getDefinition(self::DOCUMENT_BUILDER_FACTORY_SERVICE_ID)
            ->setArgument('$documentBuilders', $documentBuilders)
            ->setArgument('$container', ServiceLocatorTagPass::register($container, $services));
    }
}
