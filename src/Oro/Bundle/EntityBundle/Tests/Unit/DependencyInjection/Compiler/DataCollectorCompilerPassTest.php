<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

use Oro\Bundle\EntityBundle\DataCollector\DuplicateQueriesDataCollector;
use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\DataCollectorCompilerPass;

class DataCollectorCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DataCollectorCompilerPass
     */
    protected $compilerPass;

    protected function setUp()
    {
        $this->compilerPass = new DataCollectorCompilerPass();
    }

    public function testProcess()
    {
        $connections = [
            'default' => 'doctrine.dbal.logger.profiling.default',
            'search' => 'doctrine.dbal.logger.profiling.search',
            'config' => 'doctrine.dbal.logger.profiling.config',
        ];

        $collectorDefinition = new Definition(DuplicateQueriesDataCollector::class);

        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $containerBuilder */
        $containerBuilder = $this->getMock(ContainerBuilder::class);

        $containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with('oro_entity.profiler.duplicate_queries_data_collector')
            ->willReturn($collectorDefinition);

        $containerBuilder->expects($this->exactly(count($connections)))
            ->method('has')
            ->willReturn(true);

        /** @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject $doctrine */
        $doctrine = $this->getMock(RegistryInterface::class);

        $doctrine->expects($this->once())
            ->method('getConnectionNames')
            ->willReturn($connections);

        $containerBuilder->expects($this->once())
            ->method('get')
            ->with('doctrine')
            ->willReturn($doctrine);

        $this->compilerPass->process($containerBuilder);

        $this->assertSameSize($connections, $collectorDefinition->getMethodCalls());
        $methodCalls = $collectorDefinition->getMethodCalls();
        foreach ($connections as $connectionName => $serviceId) {
            $methodCall = current($methodCalls);
            $arguments = $methodCall[1];
            $this->assertEquals($connectionName, $arguments[0]);
            $this->assertEquals($serviceId, (string)$arguments[1]);
            next($methodCalls);
        }
    }
}
