<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;

/**
 * Registers document builders for all supported Data API request types.
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
        $documentBuilders = [];
        $taggedServices = $container->findTaggedServiceIds(self::DOCUMENT_BUILDER_TAG);
        foreach ($taggedServices as $id => $attributes) {
            $definition = $container->getDefinition($id);
            if ($definition->isShared()) {
                throw new LogicException(sprintf('The document builder service "%s" should be non shared.', $id));
            }
            $definition->setPublic(true);
            foreach ($attributes as $tagAttributes) {
                $documentBuilders[DependencyInjectionUtil::getPriority($tagAttributes)][] = [
                    $id,
                    DependencyInjectionUtil::getRequestType($tagAttributes)
                ];
            }
        }
        if (empty($documentBuilders)) {
            return;
        }

        $documentBuilders = DependencyInjectionUtil::sortByPriorityAndFlatten($documentBuilders);

        $container->getDefinition(self::DOCUMENT_BUILDER_FACTORY_SERVICE_ID)
            ->replaceArgument(0, $documentBuilders);
    }
}
