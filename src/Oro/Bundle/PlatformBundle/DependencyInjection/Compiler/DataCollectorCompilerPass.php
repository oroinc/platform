<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection\Compiler;

use Symfony\Bundle\SecurityBundle\DataCollector\SecurityDataCollector;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DataCollector\EventDataCollector;

/**
 * Temporary disables data collectors which cannot save debug info in a short time.
 */
class DataCollectorCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $ids = array_keys($container->findTaggedServiceIds('data_collector'));

        foreach ($ids as $id) {
            $definition = $container->getDefinition($id);

            if (is_a($definition->getClass(), EventDataCollector::class, true) ||
                is_a($definition->getClass(), SecurityDataCollector::class, true)
            ) {
                $container->removeDefinition($id);
            }
        }
    }
}
