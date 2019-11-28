<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection\Compiler;

use Oro\Bundle\PlatformBundle\Profiler\RepeatableFileProfilerStorage;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Profiler\FileProfilerStorage;

/**
 * Removing profiler storage decorator in case when used not FileProfilerStorage
 */
class ProfilerStorageCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('profiler.storage')) {
            return;
        }

        $profilerStorageDef = $container->getDefinition('profiler.storage');
        if (!is_a($profilerStorageDef->getClass(), FileProfilerStorage::class, true)) {
            return;
        }

        $definition = new Definition(RepeatableFileProfilerStorage::class, $profilerStorageDef->getArguments());
        $definition->setDecoratedService('profiler.storage');

        $container->setDefinition('oro_platform.profiler.storage', $definition);
    }
}
