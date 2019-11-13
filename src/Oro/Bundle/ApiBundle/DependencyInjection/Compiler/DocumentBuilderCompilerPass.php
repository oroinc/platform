<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
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
    private const DOCUMENT_BUILDER_FACTORY_SERVICE_ID = 'oro_api.document_builder_factory';
    private const DOCUMENT_BUILDER_TAG                = 'oro.api.document_builder';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $services = [];
        $documentBuilders = [];
        $taggedServices = $container->findTaggedServiceIds(self::DOCUMENT_BUILDER_TAG);
        foreach ($taggedServices as $id => $attributes) {
            if ($container->getDefinition($id)->isShared()) {
                throw new LogicException(sprintf('The document builder service "%s" should be non shared.', $id));
            }
            $services[$id] = new Reference($id);
            foreach ($attributes as $tagAttributes) {
                $documentBuilders[DependencyInjectionUtil::getPriority($tagAttributes)][] = [
                    $id,
                    DependencyInjectionUtil::getRequestType($tagAttributes)
                ];
            }
        }

        if ($documentBuilders) {
            $documentBuilders = DependencyInjectionUtil::sortByPriorityAndFlatten($documentBuilders);
        }

        $container->getDefinition(self::DOCUMENT_BUILDER_FACTORY_SERVICE_ID)
            ->setArgument(0, $documentBuilders)
            ->setArgument(1, ServiceLocatorTagPass::register($container, $services));
    }
}
