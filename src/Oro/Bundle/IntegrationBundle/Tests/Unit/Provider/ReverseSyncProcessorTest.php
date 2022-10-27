<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\ImportExport\Job\Executor;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Provider\ReverseSyncProcessor;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestContext;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestTwoWayConnector as TestConnector;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ReverseSyncProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProcessorRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $processorRegistry;

    /** @var Executor|\PHPUnit\Framework\MockObject\MockObject */
    private $jobExecutor;

    /** @var TypesRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var Integration|\PHPUnit\Framework\MockObject\MockObject */
    private $integration;

    /** @var LoggerStrategy|\PHPUnit\Framework\MockObject\MockObject */
    private $log;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    protected function setUp(): void
    {
        $this->processorRegistry = $this->createMock(ProcessorRegistry::class);
        $this->jobExecutor = $this->createMock(Executor::class);
        $this->registry = $this->createMock(TypesRegistry::class);
        $this->integration = $this->createMock(Integration::class);
        $this->log = $this->createMock(LoggerStrategy::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    }

    public function testProcess()
    {
        $connectors = 'test';
        $params = [];
        $realConnector = new TestConnector();

        $this->registry->expects($this->any())
            ->method('getConnectorType')
            ->willReturn($realConnector);

        $processor = $this->getReverseSyncProcessor(['processExport', 'addConnectorStatusAndFlush']);
        $processor->process($this->integration, $connectors, $params);
    }

    public function testOneIntegrationConnectorProcess()
    {
        $connector = 'testConnector';

        $this->integration->expects($this->never())
            ->method('getConnectors');

        $this->integration->expects($this->once())
            ->method('getId')
            ->willReturn('testChannel');

        $expectedAlias = 'test_alias';
        $this->processorRegistry->expects($this->once())
            ->method('getProcessorAliasesByEntity')
            ->with(ProcessorRegistry::TYPE_EXPORT)
            ->willReturn([$expectedAlias]);

        $realConnector = new TestConnector();

        $this->registry->expects($this->once())
            ->method('getConnectorType')
            ->willReturn($realConnector);

        $this->integration->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $jobResult = new JobResult();
        $jobResult->setContext(new TestContext());
        $jobResult->setSuccessful(true);

        $this->jobExecutor->expects($this->once())
            ->method('executeJob')
            ->with(
                'export',
                'tstJobName',
                [
                    'export' => [
                        'entityName'     => 'testEntity',
                        'channel'        => 'testChannel',
                        'processorAlias' => $expectedAlias,
                        'testParameter'  => 'testValue'
                    ]
                ]
            )
            ->willReturn($jobResult);

        $processor = $this->getReverseSyncProcessor(['addConnectorStatusAndFlush']);
        $processor->process($this->integration, $connector, ['testParameter' => 'testValue']);
    }

    private function getReverseSyncProcessor(array $mockedMethods): ReverseSyncProcessor
    {
        return $this->getMockBuilder(ReverseSyncProcessor::class)
            ->onlyMethods($mockedMethods)
            ->setConstructorArgs([
                $this->createMock(ManagerRegistry::class),
                $this->processorRegistry,
                $this->jobExecutor,
                $this->registry,
                $this->eventDispatcher,
                $this->log
            ])
            ->getMock();
    }
}
