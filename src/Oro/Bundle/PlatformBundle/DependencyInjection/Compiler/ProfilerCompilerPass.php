<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection\Compiler;

use Oro\Bundle\PlatformBundle\Monolog\DeprecationDebugProcessor;
use Oro\Bundle\PlatformBundle\Profiler\ConfigurableProfiler;
use Oro\Bundle\PlatformBundle\Profiler\DynamicallyTraceableVoter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * - Overrides debug processor to allow disable of deprecations
 * - Overrides profiler to allow data collectors toggle configuration
 * - Disables deprecations tracking in the cache warmer service based on container param
 * - Overrides traceable voter to dynamically disable the event propagation when the security data collector is disabled
 */
class ProfilerCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $debug = $container->getParameter('kernel.debug');
        if ($container->hasDefinition('profiler')) {
            if ($container->hasDefinition('debug.log_processor')) {
                $container->getDefinition('debug.log_processor')
                    ->setClass(DeprecationDebugProcessor::class)
                    ->addMethodCall('setCollectDeprecations', ['%oro_platform.collect_deprecations%']);
            }
            $container->getDefinition('profiler')
                ->setClass(ConfigurableProfiler::class);
        }
        $container->getDefinition('cache_warmer')
            ->setArgument(1, '%oro_platform.collect_deprecations%');
        if ($debug) {
            $decisionManager = $container->getDefinition('security.access.decision_manager');
            $voters = $decisionManager->getArgument(0)->getValues();
            foreach ($voters as $voterReference) {
                $voterDefinition = $container->getDefinition((string)$voterReference);
                $voterDefinition->setClass(DynamicallyTraceableVoter::class);
            }
        }
    }
}
