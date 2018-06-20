<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all request type depended Data API documentation providers.
 */
class DocumentationProviderCompilerPass implements CompilerPassInterface
{
    private const CHAIN_PROVIDER_SERVICE_ID = 'oro_api.api_doc.documentation_provider';
    private const PROVIDER_TAG              = 'oro.api.documentation_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $providers = [];
        $taggedServices = $container->findTaggedServiceIds(self::PROVIDER_TAG);
        foreach ($taggedServices as $id => $attributes) {
            foreach ($attributes as $tagAttributes) {
                $providers[DependencyInjectionUtil::getPriority($tagAttributes)][] = new Reference($id);
            }
        }
        if (empty($providers)) {
            return;
        }

        $providers = DependencyInjectionUtil::sortByPriorityAndFlatten($providers);

        $container->getDefinition(self::CHAIN_PROVIDER_SERVICE_ID)
            ->replaceArgument(0, $providers);
    }
}
