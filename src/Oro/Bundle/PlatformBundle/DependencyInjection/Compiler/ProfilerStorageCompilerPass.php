<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection\Compiler;

use Oro\Bundle\PlatformBundle\Profiler\RepeatableFileProfilerStorage;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Profiler\FileProfilerStorage;

/**
 * - Removes profiler storage decorator in case when used not FileProfilerStorage
 * - Removes PHPStan property info extractor
 */
class ProfilerStorageCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        // PhpStan extractor has a bad performance and we don't use its features yet, so we disable it
        if ($container->hasDefinition('property_info.phpstan_extractor')) {
            $container->removeDefinition('property_info.phpstan_extractor');
        }

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
