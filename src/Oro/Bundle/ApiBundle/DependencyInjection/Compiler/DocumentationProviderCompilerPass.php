<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all request type depended API documentation providers.
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
        $services = [];
        $providers = [];
        $taggedServices = $container->findTaggedServiceIds(self::PROVIDER_TAG);
        foreach ($taggedServices as $id => $attributes) {
            $services[$id] = new Reference($id);
            foreach ($attributes as $tagAttributes) {
                $providers[DependencyInjectionUtil::getPriority($tagAttributes)][] = [
                    $id,
                    DependencyInjectionUtil::getRequestType($tagAttributes)
                ];
            }
        }

        if ($providers) {
            $providers = DependencyInjectionUtil::sortByPriorityAndFlatten($providers);
        }

        $container->getDefinition(self::CHAIN_PROVIDER_SERVICE_ID)
            ->setArgument(0, $providers)
            ->setArgument(1, ServiceLocatorTagPass::register($container, $services));
    }
}
