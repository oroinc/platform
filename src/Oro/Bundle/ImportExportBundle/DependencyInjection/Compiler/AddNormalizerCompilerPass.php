<?php

namespace Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all normalizers and encoders for the import export Serializer service.
 */
class AddNormalizerCompilerPass implements CompilerPassInterface
{
    use TaggedServiceTrait;

    public function process(ContainerBuilder $container)
    {
        $serializerDef = $container->getDefinition('oro_importexport.serializer');
        $serializerDef->replaceArgument(
            0,
            $this->findAndSortTaggedServices('oro_importexport.normalizer', $container)
        );
        $serializerDef->replaceArgument(
            1,
            array_merge(
                $serializerDef->getArgument(1),
                $this->findAndSortTaggedServices('serializer.encoder', $container)
            )
        );
    }

    /**
     * @param string           $tagName
     * @param ContainerBuilder $container
     *
     * @return Reference[]
     */
    private function findAndSortTaggedServices(string $tagName, ContainerBuilder $container): array
    {
        $taggedServices = $container->findTaggedServiceIds($tagName);
        if (!$taggedServices) {
            throw new \RuntimeException(sprintf(
                'You must tag at least one service as "%s" to use the import export Serializer service',
                $tagName
            ));
        }

        $services = [];
        foreach ($taggedServices as $serviceId => $tags) {
            if ($container->hasDefinition($serviceId)) {
                $container->getDefinition($serviceId)->setPublic(false);
            }
            foreach ($tags as $tag) {
                $services[$this->getPriorityAttribute($tag)][] = new Reference($serviceId);
            }
        }

        return $this->sortByPriorityAndFlatten($services);
    }
}
