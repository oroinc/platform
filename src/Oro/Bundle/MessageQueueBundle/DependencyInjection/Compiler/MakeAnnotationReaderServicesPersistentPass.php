<?php

namespace Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds annotation reader and related services to the list of persistent services.
 */
class MakeAnnotationReaderServicesPersistentPass extends RegisterPersistentServicesPass
{
    /**
     * @param ContainerBuilder $container
     *
     * @return string[]
     */
    protected function getPersistentServices(ContainerBuilder $container)
    {
        $result = [];
        if ($container->hasAlias('annotation_reader')) {
            $result[] = 'annotation_reader';
            $alias = (string)$container->getAlias('annotation_reader');
            if ('annotations.cached_reader' === $alias) {
                // annotation cache
                $result[] = (string)$container->getDefinition('annotations.cached_reader')->getArgument(1);
            }
        }

        return $result;
    }
}
