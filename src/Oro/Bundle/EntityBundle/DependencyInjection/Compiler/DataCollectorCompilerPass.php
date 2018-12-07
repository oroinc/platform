<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Oro\Bundle\EntityBundle\Cache\LoggingHydratorWarmer;
use Oro\Bundle\EntityBundle\DataCollector\OrmLogger;
use Oro\Bundle\EntityBundle\DataCollector\ProfilingEntityManager;
use Oro\Bundle\EntityBundle\DataCollector\ProfilingManagerRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configures "orm" and "duplicate_queries" data collectors in case the profiler is enabled.
 */
class DataCollectorCompilerPass implements CompilerPassInterface
{
    private const PROFILING_LOGGER_SERVICE_ID  = 'oro_entity.profiler.orm_logger';
    private const LOGGING_HYDRATORS_PARAM_NAME = 'oro_entity.orm.hydrators';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('profiler')) {
            $this->configureDataCollectors($container);
        } else {
            $container->getParameterBag()->remove(self::LOGGING_HYDRATORS_PARAM_NAME);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function configureDataCollectors(ContainerBuilder $container)
    {
        $this->configureDuplicateQueriesDataCollector($container);
        $this->configureOrmDataCollector($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    private function configureDuplicateQueriesDataCollector(ContainerBuilder $container)
    {
        $dataCollectorDef = $container->getDefinition('oro_entity.profiler.duplicate_queries_data_collector');
        $connectionNames = $container->get('doctrine')->getConnectionNames();
        foreach ($connectionNames as $name => $serviceId) {
            $loggerId = 'doctrine.dbal.logger.profiling.' . $name;
            if ($container->has($loggerId)) {
                $dataCollectorDef->addMethodCall('addLogger', [$name, new Reference($loggerId)]);
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function configureOrmDataCollector(ContainerBuilder $container)
    {
        $this->configureLoggingHydrators($container);
        $this->configureLoggingHydratorCacheWarmer($container);
        $this->configureProfilingLogger($container);
        $this->configureManagerRegistry($container);
        $container->getDefinition('oro_entity.profiler.orm_data_collector')
            ->addArgument(new Reference(self::PROFILING_LOGGER_SERVICE_ID));
    }

    /**
     * @param ContainerBuilder $container
     */
    private function configureLoggingHydrators(ContainerBuilder $container)
    {
        $hydrators = [];
        foreach ($container->getParameter(self::LOGGING_HYDRATORS_PARAM_NAME) as $key => $value) {
            if (defined($key)) {
                $key = constant($key);
            }
            $value['loggingClass'] = 'OroLoggingHydrator\Logging' . $value['name'];
            $hydrators[$key] = $value;
        }
        $container->setParameter(self::LOGGING_HYDRATORS_PARAM_NAME, $hydrators);
    }

    /**
     * @param ContainerBuilder $container
     */
    private function configureLoggingHydratorCacheWarmer(ContainerBuilder $container)
    {
        $cacheWarmerDef = $container->register(
            'oro_entity.cache.warmer.logging_hydrator',
            LoggingHydratorWarmer::class
        );
        $cacheWarmerDef->setPublic(false);
        $cacheWarmerDef->addArgument('%' . self::LOGGING_HYDRATORS_PARAM_NAME . '%');
        $cacheWarmerDef->addTag('kernel.cache_warmer', ['priority' => 30]);
    }

    /**
     * @param ContainerBuilder $container
     */
    private function configureProfilingLogger(ContainerBuilder $container)
    {
        $loggerDef = $container->register(
            self::PROFILING_LOGGER_SERVICE_ID,
            OrmLogger::class
        );
        $loggerDef->setPublic(false);
        $loggerDef->addArgument(
            new Reference('debug.stopwatch', ContainerInterface::NULL_ON_INVALID_REFERENCE)
        );
    }

    /**
     * @param ContainerBuilder $container
     */
    private function configureManagerRegistry(ContainerBuilder $container)
    {
        $container->setParameter(
            'doctrine.orm.entity_manager.class',
            ProfilingEntityManager::class
        );
        $container->getDefinition('doctrine')
            ->setClass(ProfilingManagerRegistry::class)
            ->addMethodCall('setProfilingLogger', [new Reference(self::PROFILING_LOGGER_SERVICE_ID)])
            ->addMethodCall('setLoggingHydrators', ['%' . self::LOGGING_HYDRATORS_PARAM_NAME . '%']);
    }
}
