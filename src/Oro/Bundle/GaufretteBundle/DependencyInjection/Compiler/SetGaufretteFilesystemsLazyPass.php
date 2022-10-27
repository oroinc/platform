<?php

namespace Oro\Bundle\GaufretteBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Marks all Gaufrette filesystem services as "lazy" to prevent loading of them on each request.
 */
class SetGaufretteFilesystemsLazyPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $filesystemMapDef = $container->getDefinition('knp_gaufrette.filesystem_map');
        if (count($filesystemMapDef->getArguments()) === 0 || !\is_array($filesystemMapDef->getArgument(0))) {
            throw new \InvalidArgumentException(
                'It is expected that the argument "0" of the "knp_gaufrette.filesystem_map" service is an array.'
            );
        }
        foreach ($filesystemMapDef->getArgument(0) as $name => $reference) {
            if (!$reference instanceof Reference) {
                throw new \InvalidArgumentException(sprintf(
                    'It is expected that each element of the Gaufrette filesystem map is an instance of "%s",'
                    . ' got "%s" for the "%s" filesystem.',
                    Reference::class,
                    \is_object($reference) ? \get_class($reference) : \gettype($reference),
                    $name
                ));
            }
            $container->getDefinition((string)$reference)->setLazy(true);
        }
    }
}
