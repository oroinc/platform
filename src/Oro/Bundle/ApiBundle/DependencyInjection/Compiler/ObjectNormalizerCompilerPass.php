<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all object normalizers that are used to normalise different kind of objects.
 */
class ObjectNormalizerCompilerPass implements CompilerPassInterface
{
    private const OBJECT_NORMALIZER_REGISTRY_SERVICE_ID = 'oro_api.object_normalizer_registry';
    private const OBJECT_NORMALIZER_TAG                 = 'oro.api.object_normalizer';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $services = [];
        $normalizers = [];
        $taggedServices = $container->findTaggedServiceIds(self::OBJECT_NORMALIZER_TAG);
        foreach ($taggedServices as $id => $attributes) {
            $services[$id] = new Reference($id);
            foreach ($attributes as $tagAttributes) {
                $normalizers[DependencyInjectionUtil::getPriority($tagAttributes)][] = [
                    $id,
                    $tagAttributes['class'],
                    DependencyInjectionUtil::getRequestType($tagAttributes)
                ];
            }
        }
        if ($normalizers) {
            $normalizers = DependencyInjectionUtil::sortByPriorityAndFlatten($normalizers);
        }

        $container->getDefinition(self::OBJECT_NORMALIZER_REGISTRY_SERVICE_ID)
            ->replaceArgument(0, $normalizers)
            ->replaceArgument(1, ServiceLocatorTagPass::register($container, $services));
    }
}
