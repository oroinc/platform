<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider;

use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Provider\SyncProcessor;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\TestContext;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Stub\TestConnector;

class SyncProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var Integration|\PHPUnit\Framework\MockObject\MockObject */
    protected $integration;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $processorRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $jobExecutor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $log;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $eventDispatcher;

    /**
     * Setup test obj and mock
     */
    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['createQueryBuilder', 'getRepository'])
            ->getMock();

        $this->processorRegistry = $this->createMock('Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry');

        $this->jobExecutor = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\ImportExport\Job\Executor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->createMock('Oro\Bundle\IntegrationBundle\Manager\TypesRegistry');
        $this->integration = $this->createMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $this->log = $this->createMock('Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy');
        $this->eventDispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
    }

    protected function tearDown()
    {
        unset(
            $this->em,
            $this->eventDispatcher,
            $this->processorRegistry,
            $this->registry,
            $this->jobExecutor,
            $this->processor,
            $this->log
        );
    }

    /**
     * @dataProvider processDataProvider
     */
    public function testProcess($data, $expected)
    {
        $this->integration
            ->expects($this->once())
            ->method('getConnectors')
            ->willReturn($data['integrationConnectors']);

        $this->integration
            ->expects($this->any())
            ->method('getId')
            ->willReturn($data['channel']);

        $this->integration
            ->expects($this->atLeastOnce())
            ->method('getType')
            ->willReturn($data['integrationType']);

        $this->integration
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $this->registry
            ->expects($this->any())
            ->method('getConnectorType')
            ->willReturnMap($data['realConnectorsMap']);

        $this->processorRegistry
            ->expects($this->any())
            ->method('getProcessorAliasesByEntity')
            ->willReturn([]);

        $jobResult = new JobResult();
        $jobResult->setContext(new TestContext());
        $jobResult->setSuccessful(true);
        $mocker = $this->jobExecutor->expects($this->exactly(count($expected)))
            ->method('executeJob');
        call_user_func_array([$mocker, 'withConsecutive'], $expected);
        $mocker->willReturn($jobResult);

        $processor = $this->getSyncProcessor();
        $processor->process($this->integration, $data['connector'], $data['parameters']);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function processDataProvider()
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

    /**
     * Return mocked sync processor
     *
     * @param array $mockedMethods
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|SyncProcessor
     */
    protected function getSyncProcessor($mockedMethods = null)
    {
        $repository = $this
            ->getMockBuilder('Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $registry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
        $registry->expects($this->any())->method('getManager')
            ->will($this->returnValue($this->em));
        $registry->expects($this->any())->method('getRepository')
            ->will($this->returnValue($repository));

        return $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Provider\SyncProcessor')
            ->setMethods($mockedMethods)
            ->setConstructorArgs([
                $registry,
                $this->processorRegistry,
                $this->jobExecutor,
                $this->registry,
                $this->eventDispatcher,
                $this->log
            ])
            ->getMock();
    }

    /**
     * @param string $type
     * @param string $job
     * @param string $entity
     * @param bool   $isAllowed
     * @param int    $order
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function prepareConnectorStub($type, $job, $entity, $isAllowed, $order)
    {
        $contextRegistryMock = $this->createMock('Oro\Bundle\ImportExportBundle\Context\ContextRegistry');
        $contextMediatorMock = $this
            ->getMockBuilder('Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator')
            ->disableOriginalConstructor()
            ->getMock();
        $logger = $this->createMock('Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy');

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
