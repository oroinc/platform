<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\IntegrationBundle\ImportExport\Job\Executor;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Oro\Bundle\IntegrationBundle\Provider\SyncProcessor;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestContext;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Stub\TestConnector;
use Oro\Component\Config\Common\ConfigObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SyncProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var Integration|\PHPUnit\Framework\MockObject\MockObject */
    private $integration;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var ProcessorRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $processorRegistry;

    /** @var Executor|\PHPUnit\Framework\MockObject\MockObject */
    private $jobExecutor;

    /** @var TypesRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var LoggerStrategy|\PHPUnit\Framework\MockObject\MockObject */
    private $log;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->processorRegistry = $this->createMock(ProcessorRegistry::class);
        $this->jobExecutor = $this->createMock(Executor::class);
        $this->registry = $this->createMock(TypesRegistry::class);
        $this->integration = $this->createMock(Integration::class);
        $this->log = $this->createMock(LoggerStrategy::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    }

    /**
     * @dataProvider processDataProvider
     */
    public function testProcess(array $data, array $expected)
    {
        $this->integration->expects($this->once())
            ->method('getConnectors')
            ->willReturn($data['integrationConnectors']);
        $this->integration->expects($this->any())
            ->method('getId')
            ->willReturn($data['channel']);
        $this->integration->expects($this->atLeastOnce())
            ->method('getType')
            ->willReturn($data['integrationType']);
        $this->integration->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->registry->expects($this->any())
            ->method('getConnectorType')
            ->willReturnMap($data['realConnectorsMap']);

        $this->processorRegistry->expects($this->any())
            ->method('getProcessorAliasesByEntity')
            ->willReturn([]);

        $jobResult = new JobResult();
        $jobResult->setContext(new TestContext());
        $jobResult->setSuccessful(true);
        $this->jobExecutor->expects($this->exactly(count($expected)))
            ->method('executeJob')
            ->withConsecutive(...$expected)
            ->willReturn($jobResult);

        $processor = $this->getSyncProcessor();
        $processor->process($this->integration, $data['connector'], $data['parameters']);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processDataProvider(): array
    {
        return [
            'Single Connector Processing' => [
                'data'     => [
                    'connector'             => 'testConnector',
                    'integrationConnectors' => [$testConnector = 'testConnector', 'otherConnector'],
                    'integrationType'       => $integrationType = 'testChannelType',
                    'channel'               => $channel = 'testChannel',
                    'parameters'            => ['testParameter' => 'testValue'],
                    'realConnectorsMap'     => [
                        [
                            $integrationType,
                            $testConnector,
                            $this->prepareConnectorStub(
                                $testConnector,
                                $job = 'test job',
                                $entity = 'testEntity',
                                true,
                                100
                            )
                        ]
                    ]
                ],
                'expected' => [
                    [
                        'import',
                        $job,
                        [
                            'import' => [
                                'processorAlias' => false,
                                'entityName'     => $entity,
                                'channel'        => $channel,
                                'channelType'    => $integrationType,
                                'testParameter'  => 'testValue'
                            ]
                        ]
                    ]
                ]
            ],
            'Multiple ordered connectors, last not allowed and should be skipped' => [
                'data'     => [
                    'connector'             => null,
                    'integrationConnectors' => [
                        $firstTestConnector = 'firstTestConnector',
                        $secondTestConnector = 'secondTestConnector',
                        $thirdTestConnector = 'thirdTestConnector',
                        $fourthTestConnector = 'fourthTestConnector',
                        $fifthTestConnector = 'fifthTestConnector',
                    ],
                    'integrationType'       => $integrationType = 'testChannelType',
                    'channel'               => $channel = 'testChannel',
                    'parameters'            => [],
                    'realConnectorsMap'     => [
                        [
                            $integrationType,
                            $firstTestConnector,
                            $this->prepareConnectorStub(
                                $firstTestConnector,
                                $firstTestConnectorJob = 'first test job',
                                $firstTestConnectorEntity = 'firstTestEntity',
                                true,
                                200
                            )
                        ],
                        [
                            $integrationType,
                            $secondTestConnector,
                            $this->prepareConnectorStub(
                                $secondTestConnector,
                                $secondTestConnectorJob = 'second test job',
                                $secondTestConnectorEntity = 'secondTestEntity',
                                true,
                                100
                            )
                        ],
                        [
                            $integrationType,
                            $thirdTestConnector,
                            $this->prepareConnectorStub(
                                $thirdTestConnector,
                                $thirdTestConnectorJob = 'third test job',
                                $thirdTestConnectorEntity = 'thirdTestEntity',
                                false,
                                50
                            )
                        ],
                        [
                            $integrationType,
                            $fourthTestConnector,
                            $this->prepareConnectorStub(
                                $fourthTestConnector,
                                $fourthTestConnectorJob = 'fourth test job',
                                $fourthTestConnectorEntity = 'fourthTestEntity',
                                true,
                                50
                            )
                        ],
                        [
                            $integrationType,
                            $fifthTestConnector,
                            $this->prepareConnectorStub(
                                $fifthTestConnector,
                                $fifthTestConnectorJob = 'fifth test job',
                                $fifthTestConnectorEntity = 'fifthTestEntity',
                                true,
                                50
                            )
                        ]
                    ]
                ],
                'expected' => [
                    [
                        'import',
                        $fourthTestConnectorJob,
                        [
                            'import' => [
                                'processorAlias' => false,
                                'entityName'     => $fourthTestConnectorEntity,
                                'channel'        => $channel,
                                'channelType'    => $integrationType
                            ]
                        ]
                    ],
                    [
                        'import',
                        $fifthTestConnectorJob,
                        [
                            'import' => [
                                'processorAlias' => false,
                                'entityName'     => $fifthTestConnectorEntity,
                                'channel'        => $channel,
                                'channelType'    => $integrationType
                            ]
                        ]
                    ],
                    [
                        'import',
                        $secondTestConnectorJob,
                        [
                            'import' => [
                                'processorAlias' => false,
                                'entityName'     => $secondTestConnectorEntity,
                                'channel'        => $channel,
                                'channelType'    => $integrationType
                            ]
                        ]
                    ],
                    [
                        'import',
                        $firstTestConnectorJob,
                        [
                            'import' => [
                                'processorAlias' => false,
                                'entityName'     => $firstTestConnectorEntity,
                                'channel'        => $channel,
                                'channelType'    => $integrationType
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    public function testProcessImportWithErrorsAndWarningsLoggingEnabled(): void
    {
        $errors = ['Error 1', 'Error 2'];

        $testConnector = 'testConnector';
        $integrationType = 'testChannelType';
        $channel = 'testChannel';
        $realConnectorsMap = [
            [
                $integrationType,
                $testConnector,
                $this->prepareConnectorStub(
                    $testConnector,
                    $job = 'test job',
                    $entity = 'testEntity',
                    true,
                    100
                )
            ]
        ];

        $expected = [
            [
                'import',
                $job,
                [
                    'import' => [
                        'processorAlias' => false,
                        'entityName'     => $entity,
                        'channel'        => $channel,
                        'channelType'    => $integrationType,
                        'testParameter'  => 'testValue'
                    ]
                ]
            ]
        ];

        $this->integration->expects($this->once())
            ->method('getConnectors')
            ->willReturn([$testConnector]);
        $this->integration->expects($this->any())
            ->method('getId')
            ->willReturn($channel);
        $this->integration->expects($this->atLeastOnce())
            ->method('getType')
            ->willReturn($integrationType);
        $this->integration->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->registry->expects($this->any())
            ->method('getConnectorType')
            ->willReturnMap($realConnectorsMap);

        $this->processorRegistry->expects($this->any())
            ->method('getProcessorAliasesByEntity')
            ->willReturn([]);

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->once())
            ->method('getErrors')
            ->willReturn($errors);

        $jobResult = new JobResult();
        $jobResult->setContext($context);
        $jobResult->setSuccessful(true);
        $this->jobExecutor->expects($this->exactly(count($expected)))
            ->method('executeJob')
            ->withConsecutive(...$expected)
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

        $processor = $this->getSyncProcessor();
        $this->assertTrue(
            $processor->process($this->integration, $testConnector, ['testParameter' => 'testValue'])
        );
    }

    public function testProcessImportWithErrorsAndWarningsLoggingDisabled(): void
    {
        $errors = ['Error 1', 'Error 2'];

        $testConnector = 'testConnector';
        $integrationType = 'testChannelType';
        $channel = 'testChannel';
        $realConnectorsMap = [
            [
                $integrationType,
                $testConnector,
                $this->prepareConnectorStub(
                    $testConnector,
                    $job = 'test job',
                    $entity = 'testEntity',
                    true,
                    100
                )
            ]
        ];

        $expected = [
            [
                'import',
                $job,
                [
                    'import' => [
                        'processorAlias' => false,
                        'entityName'     => $entity,
                        'channel'        => $channel,
                        'channelType'    => $integrationType,
                        'testParameter'  => 'testValue'
                    ]
                ]
            ]
        ];

        $this->integration->expects($this->once())
            ->method('getConnectors')
            ->willReturn([$testConnector]);
        $this->integration->expects($this->any())
            ->method('getId')
            ->willReturn($channel);
        $this->integration->expects($this->atLeastOnce())
            ->method('getType')
            ->willReturn($integrationType);
        $this->integration->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->registry->expects($this->any())
            ->method('getConnectorType')
            ->willReturnMap($realConnectorsMap);

        $this->processorRegistry->expects($this->any())
            ->method('getProcessorAliasesByEntity')
            ->willReturn([]);

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->once())
            ->method('getErrors')
            ->willReturn($errors);

        $jobResult = new JobResult();
        $jobResult->setContext($context);
        $jobResult->setSuccessful(true);
        $this->jobExecutor->expects($this->exactly(count($expected)))
            ->method('executeJob')
            ->withConsecutive(...$expected)
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

        $processor = $this->getSyncProcessor();
        $this->assertTrue(
            $processor->process($this->integration, $testConnector, ['testParameter' => 'testValue'])
        );
    }

    public function testProcessImportWithJobFailure(): void
    {
        $errors = ['Error 1', 'Error 2'];

        $testConnector = 'testConnector';
        $integrationType = 'testChannelType';
        $channel = 'testChannel';
        $realConnectorsMap = [
            [
                $integrationType,
                $testConnector,
                $this->prepareConnectorStub(
                    $testConnector,
                    $job = 'test job',
                    $entity = 'testEntity',
                    true,
                    100
                )
            ]
        ];

        $expected = [
            [
                'import',
                $job,
                [
                    'import' => [
                        'processorAlias' => false,
                        'entityName'     => $entity,
                        'channel'        => $channel,
                        'channelType'    => $integrationType,
                        'testParameter'  => 'testValue'
                    ]
                ]
            ]
        ];

        $this->integration->expects($this->once())
            ->method('getConnectors')
            ->willReturn([$testConnector]);
        $this->integration->expects($this->any())
            ->method('getId')
            ->willReturn($channel);
        $this->integration->expects($this->atLeastOnce())
            ->method('getType')
            ->willReturn($integrationType);
        $this->integration->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->registry->expects($this->any())
            ->method('getConnectorType')
            ->willReturnMap($realConnectorsMap);

        $this->processorRegistry->expects($this->any())
            ->method('getProcessorAliasesByEntity')
            ->willReturn([]);

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->once())
            ->method('getErrors')
            ->willReturn($errors);

        $jobResult = new JobResult();
        $jobResult->setContext($context);
        $jobResult->setSuccessful(false);
        $jobResult->setFailureExceptions([
            'Exception 1',
            'Exception 2',
        ]);
        $this->jobExecutor->expects($this->exactly(count($expected)))
            ->method('executeJob')
            ->withConsecutive(...$expected)
            ->willReturn($jobResult);

        $syncSettings = $this->createMock(ConfigObject::class);
        $syncSettings->expects($this->never())
            ->method('offsetGetOr');

        $this->integration->expects($this->never())
            ->method('getSynchronizationSettings');

        $this->log->expects($this->exactly(2))
            ->method('error')
            ->withConsecutive(
                ['Errors have occurred:'],
                ['Exception 1' . PHP_EOL . 'Exception 2']
            );

        $processor = $this->getSyncProcessor();
        $this->assertFalse(
            $processor->process($this->integration, $testConnector, ['testParameter' => 'testValue'])
        );
    }

    private function getSyncProcessor(): SyncProcessor
    {
        $repository = $this->createMock(ChannelRepository::class);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManager')
            ->willReturn($this->em);
        $registry->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        return new SyncProcessor(
            $registry,
            $this->processorRegistry,
            $this->jobExecutor,
            $this->registry,
            $this->eventDispatcher,
            $this->log
        );
    }

    private function prepareConnectorStub(
        string $type,
        string $job,
        string $entity,
        bool $isAllowed,
        int $order
    ): TestConnector {
        $contextRegistryMock = $this->createMock(ContextRegistry::class);
        $contextMediatorMock = $this->createMock(ConnectorContextMediator::class);
        $logger = $this->createMock(LoggerStrategy::class);

        /**
         * Mock was not used because of warning in usort.
         * This warning appear when mock object used
         */
        $connector = new TestConnector($contextRegistryMock, $logger, $contextMediatorMock);

        $connector->type = $type;
        $connector->job = $job;
        $connector->entityName = $entity;
        $connector->allowed = $isAllowed;
        $connector->order = $order;

        return $connector;
    }
}
