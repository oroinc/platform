<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection\Compiler;

use Doctrine\ORM\Query;
use Oro\Bundle\EntityBundle\Cache\LoggingHydratorWarmer;
use Oro\Bundle\EntityBundle\DataCollector\DuplicateQueriesDataCollector;
use Oro\Bundle\EntityBundle\DataCollector\OrmDataCollector;
use Oro\Bundle\EntityBundle\DataCollector\OrmLogger;
use Oro\Bundle\EntityBundle\DataCollector\ProfilingManagerRegistry;
use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\DataCollectorCompilerPass;
use Oro\Bundle\EntityBundle\ORM\Registry;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class DataCollectorCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var DataCollectorCompilerPass */
    private $compilerPass;

    protected function setUp()
    {
        $this->compilerPass = new DataCollectorCompilerPass();
    }

    public function testConfigureWhenProfilingIsDisabled()
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->setParameter('oro_entity.orm.hydrators', []);
        $containerBuilder->register('oro_entity.profiler.duplicate_queries_data_collector');
        $containerBuilder->register('oro_entity.profiler.orm_data_collector');
        $doctrine = $this->createMock(RegistryInterface::class);
        $containerBuilder->set('doctrine', $doctrine);
        $containerBuilder->register('doctrine');

        $this->compilerPass->process($containerBuilder);

        self::assertFalse($containerBuilder->hasParameter('oro_entity.orm.hydrators'));
    }

    public function testConfigureDuplicateQueriesDataCollector()
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->register('profiler');
        $containerBuilder->setParameter('oro_entity.orm.hydrators', []);
        $containerBuilder->register('oro_entity.profiler.orm_data_collector');
        $doctrine = $this->createMock(RegistryInterface::class);
        $containerBuilder->set('doctrine', $doctrine);
        $containerBuilder->register('doctrine');

        $duplicateQueriesCollectorDef = new Definition(DuplicateQueriesDataCollector::class);
        $containerBuilder->setDefinition(
            'oro_entity.profiler.duplicate_queries_data_collector',
            $duplicateQueriesCollectorDef
        );

        $connections = [
            'default' => 'doctrine.dbal.logger.profiling.default',
            'search'  => 'doctrine.dbal.logger.profiling.search',
            'config'  => 'doctrine.dbal.logger.profiling.config'
        ];
        $doctrine->expects(self::once())
            ->method('getConnectionNames')
            ->willReturn($connections);

        $defaultProfilingLoggerDef = new Definition();
        $containerBuilder->register('doctrine.dbal.logger.profiling.default', $defaultProfilingLoggerDef);
        $configProfilingLoggerDef = new Definition();
        $containerBuilder->register('doctrine.dbal.logger.profiling.config', $configProfilingLoggerDef);

        $this->compilerPass->process($containerBuilder);

        self::assertEquals(
            [
                ['addLogger', ['default', new Reference('doctrine.dbal.logger.profiling.default')]],
                ['addLogger', ['config', new Reference('doctrine.dbal.logger.profiling.config')]]
            ],
            $duplicateQueriesCollectorDef->getMethodCalls()
        );
    }

    public function testConfigureOrmDataCollector()
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->register('profiler');
        $containerBuilder->register('oro_entity.profiler.duplicate_queries_data_collector');
        $doctrine = $this->createMock(RegistryInterface::class);
        $doctrine->expects(self::once())
            ->method('getConnectionNames')
            ->willReturn([]);
        $containerBuilder->set('doctrine', $doctrine);

        $doctrineDef = new Definition(Registry::class);
        $containerBuilder->setDefinition('doctrine', $doctrineDef);

        $containerBuilder->setParameter(
            'oro_entity.orm.hydrators',
            [
                'Doctrine\ORM\Query::HYDRATE_OBJECT' => [
                    'name' => 'ObjectHydrator'
                ],
                'another_hydrator'                   => [
                    'name' => 'AnotherObjectHydrator'
                ]
            ]
        );

        $ormCollectorDef = new Definition(OrmDataCollector::class);
        $containerBuilder->setDefinition('oro_entity.profiler.orm_data_collector', $ormCollectorDef);

        $this->compilerPass->process($containerBuilder);

        self::assertEquals(
            [
                Query::HYDRATE_OBJECT => [
                    'name'         => 'ObjectHydrator',
                    'loggingClass' => 'OroLoggingHydrator\LoggingObjectHydrator'
                ],
                'another_hydrator'    => [
                    'name'         => 'AnotherObjectHydrator',
                    'loggingClass' => 'OroLoggingHydrator\LoggingAnotherObjectHydrator'
                ]
            ],
            $containerBuilder->getParameter('oro_entity.orm.hydrators')
        );

        $expectedCacheWarmerDef = new Definition(LoggingHydratorWarmer::class);
        $expectedCacheWarmerDef->setPublic(false);
        $expectedCacheWarmerDef->addArgument('%oro_entity.orm.hydrators%');
        $expectedCacheWarmerDef->addTag('kernel.cache_warmer', ['priority' => 30]);
        self::assertEquals(
            $expectedCacheWarmerDef,
            $containerBuilder->getDefinition('oro_entity.cache.warmer.logging_hydrator')
        );

        $expectedLoggerDef = new Definition(OrmLogger::class);
        $expectedLoggerDef->setPublic(false);
        $expectedLoggerDef->addArgument(
            new Reference('debug.stopwatch', ContainerInterface::NULL_ON_INVALID_REFERENCE)
        );
        self::assertEquals(
            $expectedLoggerDef,
            $containerBuilder->getDefinition('oro_entity.profiler.orm_logger')
        );

        self::assertEquals(
            [new Reference('oro_entity.profiler.orm_logger')],
            $ormCollectorDef->getArguments()
        );

        self::assertEquals(ProfilingManagerRegistry::class, $doctrineDef->getClass());
        self::assertEquals(
            [
                ['setProfilingLogger', [new Reference('oro_entity.profiler.orm_logger')]],
                ['setLoggingHydrators', ['%oro_entity.orm.hydrators%']]
            ],
            $doctrineDef->getMethodCalls()
        );
    }
}
