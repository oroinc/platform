<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;

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
        // find document builders
        $documentBuilders = [];
        $taggedServices = $container->findTaggedServiceIds(self::DOCUMENT_BUILDER_TAG);
        foreach ($taggedServices as $id => $attributes) {
            $definition = DependencyInjectionUtil::findDefinition($container, $id);
            if (!$definition->isPublic() || $definition->isShared()) {
                throw new LogicException(
                    sprintf('The document builder service "%s" should be public and non shared.', $id)
                );
            }
            foreach ($attributes as $tagAttributes) {
                $documentBuilders[DependencyInjectionUtil::getPriority($tagAttributes)][] = [
                    $id,
                    DependencyInjectionUtil::getAttribute($tagAttributes, 'requestType', null)
                ];
            }
        }
        if (empty($documentBuilders)) {
            return;
        }

        // sort by priority and flatten
        $documentBuilders = DependencyInjectionUtil::sortByPriorityAndFlatten($documentBuilders);

        // register
        $container->getDefinition(self::DOCUMENT_BUILDER_FACTORY_SERVICE_ID)
            ->replaceArgument(0, $documentBuilders);
    }
}
