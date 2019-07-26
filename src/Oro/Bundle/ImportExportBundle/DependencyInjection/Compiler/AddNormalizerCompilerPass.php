<?php

namespace Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AddNormalizerCompilerPass implements CompilerPassInterface
{
    const SERIALIZER_SERVICE = 'oro_importexport.serializer';
    const ATTRIBUTE_NORMALIZER_TAG = 'oro_importexport.normalizer';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition(self::SERIALIZER_SERVICE);

        $normalizers = $this->findAndSortTaggedServices(self::ATTRIBUTE_NORMALIZER_TAG, $container);
        $definition->replaceArgument(0, $normalizers);

        $encoders = $this->findAndSortTaggedServices('serializer.encoder', $container);
        $definition->replaceArgument(1, array_merge($definition->getArgument(1), $encoders));
    }

    /**
     * @param string $tagName
     * @param ContainerBuilder $container
     * @return array
     */
    private function findAndSortTaggedServices($tagName, ContainerBuilder $container)
    {
        $services = $container->findTaggedServiceIds($tagName);

        if (empty($services)) {
            throw new \RuntimeException(
                sprintf(
                    'You must tag at least one service as "%s" to use the import export Serializer service',
                    $tagName
                )
            );
        }

        $sortedServices = array();
        foreach ($services as $serviceId => $tags) {
            if ($container->hasDefinition($serviceId)) {
                $container->getDefinition($serviceId)->setPublic(false);
            }
            foreach ($tags as $tag) {
                $priority = isset($tag['priority']) ? $tag['priority'] : 0;
                $sortedServices[$priority][] = new Reference($serviceId);
            }
        }

        krsort($sortedServices);

        // Flatten the array
        return call_user_func_array('array_merge', $sortedServices);
    }
}
