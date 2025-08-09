<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\ImportExport\Job\Executor;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Provider\ReverseSyncProcessor;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestContext;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestTwoWayConnector as TestConnector;
use Oro\Component\Config\Common\ConfigObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ReverseSyncProcessorTest extends TestCase
{
    private ProcessorRegistry&MockObject $processorRegistry;
    private Executor&MockObject $jobExecutor;
    private TypesRegistry&MockObject $registry;
    private Integration&MockObject $integration;
    private LoggerStrategy&MockObject $log;
    private EventDispatcherInterface&MockObject $eventDispatcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->processorRegistry = $this->createMock(ProcessorRegistry::class);
        $this->jobExecutor = $this->createMock(Executor::class);
        $this->registry = $this->createMock(TypesRegistry::class);
        $this->integration = $this->createMock(Integration::class);
        $this->log = $this->createMock(LoggerStrategy::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    }

    public function testProcess(): void
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

    public function testOneIntegrationConnectorProcess(): void
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

    public function testProcessExportWithErrorsAndWarningsLoggingEnabled(): void
    {
        $connector = 'testConnector';
        $errors = ['Error 1', 'Error 2'];

        $this->integration->expects($this->once())
            ->method('getId')
            ->willReturn('testChannel');

        $realConnector = new TestConnector();

        $expectedAlias = 'test_alias';
        $this->processorRegistry->expects($this->once())
            ->method('getProcessorAliasesByEntity')
            ->with(ProcessorRegistry::TYPE_EXPORT)
            ->willReturn([$expectedAlias]);

        $this->registry->expects($this->once())
            ->method('getConnectorType')
            ->willReturn($realConnector);

        $this->integration->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->once())
            ->method('getErrors')
            ->willReturn($errors);

        $jobResult = new JobResult();
        $jobResult->setContext($context);
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
                    ]
                ]
            )
            ->willReturn($jobResult);

        $syncSettings = $this->createMock(ConfigObject::class);
        $syncSettings->expects($this->once())
            ->method('offsetGetOr')
            ->with('logWarnings', false)
            ->willReturn(true);

        $this->integration->expects($this->once())
            ->method('getSynchronizationSettings')
            ->willReturn($syncSettings);

        $this->log->expects($this->exactly(2))
            ->method('error')
            ->withConsecutive(
                ['Error 1'],
                ['Error 2']
            );

        $processor = $this->getReverseSyncProcessor(['addConnectorStatusAndFlush']);

        $this->assertTrue(
            $processor->process($this->integration, $connector)
        );
    }

    public function testProcessExportWithErrorsAndWarningsLoggingDisabled(): void
    {
        $connector = 'testConnector';
        $errors = ['Error 1', 'Error 2'];

        $this->integration->expects($this->once())
            ->method('getId')
            ->willReturn('testChannel');

        $realConnector = new TestConnector();

        $expectedAlias = 'test_alias';
        $this->processorRegistry->expects($this->once())
            ->method('getProcessorAliasesByEntity')
            ->with(ProcessorRegistry::TYPE_EXPORT)
            ->willReturn([$expectedAlias]);

        $this->registry->expects($this->once())
            ->method('getConnectorType')
            ->willReturn($realConnector);

        $this->integration->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->once())
            ->method('getErrors')
            ->willReturn($errors);

        $jobResult = new JobResult();
        $jobResult->setContext($context);
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
                    ]
                ]
            )
            ->willReturn($jobResult);

        $syncSettings = $this->createMock(ConfigObject::class);
        $syncSettings->expects($this->once())
            ->method('offsetGetOr')
            ->with('logWarnings', false)
            ->willReturn(false);

        $this->integration->expects($this->once())
            ->method('getSynchronizationSettings')
            ->willReturn($syncSettings);

        $this->log->expects($this->never())
            ->method('error');

        $processor = $this->getReverseSyncProcessor(['addConnectorStatusAndFlush']);

        $this->assertTrue(
            $processor->process($this->integration, $connector)
        );
    }

    public function testProcessExportWithJobFailure(): void
    {
        $connector = 'testConnector';
        $failureExceptions = ['Exception 1', 'Exception 2'];

        $this->integration->expects($this->once())
            ->method('getId')
            ->willReturn('testChannel');

        $realConnector = new TestConnector();

        $expectedAlias = 'test_alias';
        $this->processorRegistry->expects($this->once())
            ->method('getProcessorAliasesByEntity')
            ->with(ProcessorRegistry::TYPE_EXPORT)
            ->willReturn([$expectedAlias]);

        $this->registry->expects($this->once())
            ->method('getConnectorType')
            ->willReturn($realConnector);

        $this->integration->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->once())
            ->method('getErrors')
            ->willReturn([]);

        $jobResult = new JobResult();
        $jobResult->setContext($context);
        $jobResult->setSuccessful(false); // Job failed
        $jobResult->setFailureExceptions($failureExceptions);

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
                    ]
                ]
            )
            ->willReturn($jobResult);

        $this->log->expects($this->exactly(2))
            ->method('error')
            ->withConsecutive(
                ['Errors have occurred:'],
                ['Exception 1' . PHP_EOL . 'Exception 2']
            );

        $processor = $this->getReverseSyncProcessor(['addConnectorStatusAndFlush']);

        $this->assertFalse(
            $processor->process($this->integration, $connector)
        );
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
