<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener\Extension;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;

use Oro\Bundle\WorkflowBundle\Entity\EventTriggerInterface;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessJobRepository;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessTriggerRepository;
use Oro\Bundle\WorkflowBundle\EventListener\Extension\ProcessTriggerExtension;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Bundle\WorkflowBundle\Model\ProcessHandler;
use Oro\Bundle\WorkflowBundle\Model\ProcessLogger;
use Oro\Bundle\WorkflowBundle\Model\ProcessSchedulePolicy;

class ProcessTriggerExtensionTest extends AbstractEventTriggerExtensionTest
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessTriggerRepository */
    protected $repository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessJobRepository */
    protected $processJobRepository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessHandler */
    protected $handler;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessLogger */
    protected $logger;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessSchedulePolicy */
    protected $schedulePolicy;

    /** @var \PHPUnit_Framework_MockObject_MockObject|MessageProducerInterface */
    protected $messageProducer;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->getMockBuilder(ProcessTriggerRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findAllWithDefinitions'])
            ->getMock();

        $this->processJobRepository = $this->getMockBuilder(ProcessJobRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['deleteByHashes'])
            ->getMock();

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->willReturnMap(
                [
                    [ProcessTrigger::class, $this->repository],
                    [ProcessJob::class, $this->processJobRepository]
                ]
            );

        $this->handler = $this->getMockBuilder(ProcessHandler::class)->disableOriginalConstructor()->getMock();

        $this->logger = $this->getMockBuilder(ProcessLogger::class)->disableOriginalConstructor()->getMock();

        $this->schedulePolicy = $this->getMock(ProcessSchedulePolicy::class);

        $this->messageProducer = $this->getMockBuilder(MessageProducerInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->extension = new ProcessTriggerExtension(
            $this->doctrineHelper,
            $this->handler,
            $this->logger,
            $this->triggerCache,
            $this->schedulePolicy,
            $this->messageProducer
        );
    }

    protected function tearDown()
    {
        parent::tearDown();

        unset($this->handler, $this->logger, $this->schedulePolicy, $this->repository, $this->processJobRepository);
    }

    public function testSetForceQueued()
    {
        $this->assertAttributeEquals(false, 'forceQueued', $this->extension);

        $this->extension->setForceQueued(true);

        $this->assertAttributeEquals(true, 'forceQueued', $this->extension);
    }

    public function testHasTriggers()
    {
        $this->triggerCache->expects($this->once())
            ->method('hasTrigger')
            ->with(self::ENTITY_CLASS, EventTriggerInterface::EVENT_CREATE);

        $this->extension->hasTriggers($this->getMainEntity(), EventTriggerInterface::EVENT_CREATE);
    }

    public function testScheduleCreateEvent()
    {
        $entity = $this->getMainEntity();

        $createTrigger = $this->getTriggers('create');

        $this->prepareRepository();
        $this->prepareSchedulePolicy($createTrigger, true);
        $this->prepareTriggerCache(self::ENTITY_CLASS, EventTriggerInterface::EVENT_CREATE);

        $this->callPreFunctionByEventName(EventTriggerInterface::EVENT_CREATE, $entity);

        $expectedTriggers = $this->getExpectedTriggers($this->getTriggers());
        $expectedScheduledProcessed = [
            self::ENTITY_CLASS => [
                [
                    'trigger' => $createTrigger,
                    'data' => $this->createProcessData(['data' => $entity])
                ]
            ]
        ];

        $this->assertAttributeEquals($expectedTriggers, 'triggers', $this->extension);
        $this->assertAttributeEquals($expectedScheduledProcessed, 'scheduledProcesses', $this->extension);
    }

    public function testScheduleCreateEventNotAllowSchedule()
    {
        $entity = $this->getMainEntity();

        $createTrigger = $this->getTriggers('create');

        $this->prepareRepository();
        $this->prepareSchedulePolicy($createTrigger, false);
        $this->prepareTriggerCache(self::ENTITY_CLASS, EventTriggerInterface::EVENT_CREATE);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                'Policy declined process scheduling',
                $createTrigger,
                $this->isInstanceOf('Oro\Bundle\WorkflowBundle\Model\ProcessData')
            );

        $this->callPreFunctionByEventName(EventTriggerInterface::EVENT_CREATE, $entity);

        $expectedTriggers = $this->getExpectedTriggers($this->getTriggers());

        $this->assertAttributeEquals($expectedTriggers, 'triggers', $this->extension);
        $this->assertAttributeEmpty('scheduledProcesses', $this->extension);
    }

    public function testScheduleUpdateEvent()
    {
        $entity = $this->getMainEntity();
        $oldValue = 1;
        $newValue = 2;
        $changeSet = [self::FIELD => ['old' => $oldValue, 'new' => $newValue]];

        $updateEntityTrigger = $this->getTriggers('updateEntity');
        $updateFieldTrigger = $this->getTriggers('updateField');

        $this->prepareRepository();
        $this->prepareSchedulePolicy([$updateEntityTrigger, $updateFieldTrigger], true);
        $this->prepareTriggerCache(self::ENTITY_CLASS, EventTriggerInterface::EVENT_UPDATE);

        $this->callPreFunctionByEventName(EventTriggerInterface::EVENT_UPDATE, $entity, $changeSet);

        $expectedTriggers = $this->getExpectedTriggers($this->getTriggers());
        $expectedScheduledProcessed = [
            self::ENTITY_CLASS => [
                [
                    'trigger' => $updateEntityTrigger,
                    'data'    => $this->createProcessData(
                        [
                            'data'      => $entity,
                            'changeSet' => [
                                self::FIELD => [
                                    'old' => $oldValue,
                                    'new' => $newValue
                                ]
                            ]
                        ]
                    )
                ],
                [
                    'trigger' => $updateFieldTrigger,
                    'data'    => $this->createProcessData(
                        [
                            'data'      => $entity,
                            'old'       => $oldValue,
                            'changeSet' => [
                                self::FIELD => [
                                    'old' => $oldValue,
                                    'new' => $newValue
                                ]
                            ],
                            'new'       => $newValue
                        ]
                    )
                ],
            ],
        ];

        $this->assertAttributeEquals($expectedTriggers, 'triggers', $this->extension);
        $this->assertAttributeEquals($expectedScheduledProcessed, 'scheduledProcesses', $this->extension);
    }

    public function testScheduleUpdateEventNotAllowSchedule()
    {
        $entity = $this->getMainEntity();
        $oldValue = 1;
        $newValue = 2;
        $changeSet = [self::FIELD => ['old' => $oldValue, 'new' => $newValue]];

        $updateEntityTrigger = $this->getTriggers('updateEntity');
        $updateFieldTrigger = $this->getTriggers('updateField');

        $this->prepareRepository();
        $this->prepareSchedulePolicy([$updateEntityTrigger, $updateFieldTrigger], false);
        $this->prepareTriggerCache(self::ENTITY_CLASS, EventTriggerInterface::EVENT_UPDATE);

        $this->callPreFunctionByEventName(EventTriggerInterface::EVENT_UPDATE, $entity, $changeSet);

        $expectedTriggers = $this->getExpectedTriggers($this->getTriggers());

        $this->assertAttributeEquals($expectedTriggers, 'triggers', $this->extension);
        $this->assertAttributeEmpty('scheduledProcesses', $this->extension);
    }

    public function testScheduleDeleteEvent()
    {
        $entity = $this->getMainEntity();

        $deleteTrigger = $this->getTriggers('delete');

        $this->prepareRepository();
        $this->prepareSchedulePolicy($deleteTrigger, true);
        $this->prepareTriggerCache(self::ENTITY_CLASS, EventTriggerInterface::EVENT_DELETE);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity, false)
            ->willReturn($entity->getId());

        $this->callPreFunctionByEventName(EventTriggerInterface::EVENT_DELETE, $entity);

        $expectedTriggers = $this->getExpectedTriggers($this->getTriggers());
        $expectedScheduledProcessed = [
            self::ENTITY_CLASS => [
                [
                    'trigger' => $deleteTrigger,
                    'data' => $this->createProcessData(['data' => $entity])
                ]
            ]
        ];
        $expectedEntityHashes = [ProcessJob::generateEntityHash(self::ENTITY_CLASS, $entity->getId())];

        $this->assertAttributeEquals($expectedTriggers, 'triggers', $this->extension);
        $this->assertAttributeEquals($expectedScheduledProcessed, 'scheduledProcesses', $this->extension);
        $this->assertAttributeEquals($expectedEntityHashes, 'removedEntityHashes', $this->extension);
    }

    public function testScheduleDeleteEventNotAllowSchedule()
    {
        $entity = $this->getMainEntity();

        $deleteTrigger = $this->getTriggers('delete');

        $this->prepareRepository();
        $this->prepareSchedulePolicy($deleteTrigger, false);
        $this->prepareTriggerCache(self::ENTITY_CLASS, EventTriggerInterface::EVENT_DELETE);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity, false)
            ->willReturn($entity->getId());

        $this->callPreFunctionByEventName(EventTriggerInterface::EVENT_DELETE, $entity);

        $expectedTriggers = $this->getExpectedTriggers($this->getTriggers());
        $expectedEntityHashes = [ProcessJob::generateEntityHash(self::ENTITY_CLASS, $entity->getId())];

        $this->assertAttributeEquals($expectedTriggers, 'triggers', $this->extension);
        $this->assertAttributeEmpty('scheduledProcesses', $this->extension);
        $this->assertAttributeEquals($expectedEntityHashes, 'removedEntityHashes', $this->extension);
    }

    /**
     * @dataProvider clearDataProvider
     *
     * @param string $className
     * @param bool $hasScheduledProcesses
     */
    public function testClear($className, $hasScheduledProcesses)
    {
        $createTrigger = $this->getTriggers('create');

        $this->prepareRepository();
        $this->prepareSchedulePolicy($createTrigger, true);
        $this->prepareTriggerCache(self::ENTITY_CLASS, EventTriggerInterface::EVENT_CREATE);

        $expectedTriggers = $this->getExpectedTriggers($this->getTriggers());

        $this->callPreFunctionByEventName(EventTriggerInterface::EVENT_CREATE, $this->getMainEntity());

        $this->assertAttributeEquals($expectedTriggers, 'triggers', $this->extension);
        $this->assertAttributeNotEmpty('scheduledProcesses', $this->extension);

        // test
        $this->extension->clear($className);

        $this->assertAttributeEquals(null, 'triggers', $this->extension);

        if ($hasScheduledProcesses) {
            $this->assertAttributeNotEmpty('scheduledProcesses', $this->extension);
        } else {
            $this->assertAttributeEmpty('scheduledProcesses', $this->extension);
        }
    }

    /**
     * @return array
     */
    public function clearDataProvider()
    {
        return [
            'clear all' => [
                'className' => null,
                'hasScheduledProcesses' => false
            ],
            'clear scheduled processes' => [
                'className' => self::ENTITY_CLASS,
                'hasScheduledProcesses' => false
            ],
            'clear process triggers' => [
                'className' => ProcessTrigger::class,
                'hasScheduledProcesses' => true
            ]
        ];
    }

    public function testProcessHandledProcess()
    {
        $entity = $this->getMainEntity();

        $this->handler->expects($this->once())->method('isTriggerApplicable')->willReturn(true);

        $expectedTrigger = $this->getTriggers('create');

        $this->prepareRepository();
        $this->prepareSchedulePolicy($expectedTrigger, true);
        $this->prepareTriggerCache(self::ENTITY_CLASS, EventTriggerInterface::EVENT_CREATE);

        $this->callPreFunctionByEventName(EventTriggerInterface::EVENT_CREATE, $entity);

        $expectedData = $this->createProcessData(['data' => $entity]);

        $this->entityManager->expects($this->once())->method('flush');

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Process handled', $expectedTrigger, $expectedData);

        $this->handler->expects($this->once())->method('handleTrigger')->with($expectedTrigger, $expectedData);
        $this->handler->expects($this->once())->method('finishTrigger')->with($expectedTrigger, $expectedData);

        $this->extension->process($this->entityManager);
    }

    public function testProcessHandledProcessUnmetPreConditions()
    {
        $entity = $this->getMainEntity();

        $this->handler->expects($this->any())->method('isTriggerApplicable')->willReturn(false);

        $expectedTrigger = $this->getTriggers('create');

        $this->prepareRepository();
        $this->prepareSchedulePolicy($expectedTrigger, true);
        $this->prepareTriggerCache(self::ENTITY_CLASS, EventTriggerInterface::EVENT_CREATE);

        $this->callPreFunctionByEventName(EventTriggerInterface::EVENT_CREATE, $entity);

        $expectedData = $this->createProcessData(['data' => $entity]);

        $this->entityManager->expects($this->never())->method('flush');

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Process trigger is not applicable', $expectedTrigger, $expectedData);

        $this->handler->expects($this->never())->method('handleTrigger')->with($expectedTrigger, $expectedData);
        $this->handler->expects($this->never())->method('finishTrigger')->with($expectedTrigger, $expectedData);

        $this->extension->process($this->entityManager);
    }

    /**
     * @dataProvider postFlushQueuedProcessJobProvider
     * @param array $entityParams
     */
    public function testProcessQueuedProcessJob($entityParams)
    {
        $callOrder    = 0;
        $expectedData = $this->createProcessData(['data' => $this->getMainEntity()]);
        $triggers     = [];

        $this->handler->expects($this->any())->method('isTriggerApplicable')->willReturn(true);

        foreach ($entityParams as $entityParam) {
            $expectedTrigger = $this->getCustomQueuedTrigger($entityParam);
            $triggers[]      = $expectedTrigger;
        }

        $this->prepareRepository($triggers);
        $this->prepareSchedulePolicy($triggers, true);

        /** @var ProcessTrigger $trigger */
        foreach ($triggers as $iteration => $trigger) {
            $this->logger->expects($this->at($iteration))
                ->method('debug')
                ->with('Process queued', $trigger, $expectedData);

            $this->callPreFunctionByEventName($trigger->getEvent(), $this->getMainEntity());

            $this->assertProcessJobPersist($this->entityManager, $entityParams, $iteration);
            $callOrder = $iteration;
        }

        $this->entityManager->expects($this->at(++$callOrder))->method('flush');

        $this->handler->expects($this->never())->method('handleTrigger');

        $this->extension->process($this->entityManager);

        $this->assertAttributeEmpty('queuedJobs', $this->extension);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function postFlushQueuedProcessJobProvider()
    {
        return [
            'all with same priority and timeShift (1 batch)' => [
                'entities' => [
                    EventTriggerInterface::EVENT_CREATE => [
                        'id'        => 1,
                        'event'     => EventTriggerInterface::EVENT_CREATE,
                        'priority'  => 10,
                        'timeShift' => 60
                    ],
                    EventTriggerInterface::EVENT_UPDATE => [
                        'id'        => 2,
                        'event'     => EventTriggerInterface::EVENT_UPDATE,
                        'priority'  => 10,
                        'timeShift' => 60
                    ],
                    EventTriggerInterface::EVENT_DELETE => [
                        'id'        => 3,
                        'event'     => EventTriggerInterface::EVENT_DELETE,
                        'priority'  => 10,
                        'timeShift' => 60,
                    ]
                ]
            ],
            'one with different priority (2 batch)' => [
                'entities' => [
                    EventTriggerInterface::EVENT_CREATE => [
                        'id'        => 1,
                        'event'     => EventTriggerInterface::EVENT_CREATE,
                        'priority'  => 90,
                        'timeShift' => 60
                    ],
                    EventTriggerInterface::EVENT_UPDATE => [
                        'id'        => 2,
                        'event'     => EventTriggerInterface::EVENT_UPDATE,
                        'priority'  => 10,
                        'timeShift' => 60
                    ],
                    EventTriggerInterface::EVENT_DELETE => [
                        'id'        => 3,
                        'event'     => EventTriggerInterface::EVENT_DELETE,
                        'priority'  => 10,
                        'timeShift' => 60
                    ]
                ]
            ],
            'all with same priority and different timeShift (3 batch)' => [
                'entities' => [
                    EventTriggerInterface::EVENT_CREATE => [
                        'id'        => 1,
                        'event'     => EventTriggerInterface::EVENT_CREATE,
                        'priority'  => 10,
                        'timeShift' => 10
                    ],
                    EventTriggerInterface::EVENT_UPDATE => [
                        'id'        => 2,
                        'event'     => EventTriggerInterface::EVENT_UPDATE,
                        'priority'  => 10,
                        'timeShift' => 20
                    ],
                    EventTriggerInterface::EVENT_DELETE => [
                        'id'        => 3,
                        'event'     => EventTriggerInterface::EVENT_DELETE,
                        'priority'  => 10,
                        'timeShift' => 30
                    ]
                ]
            ],
            'all with same priority and only one with different timeShift (2 batch)' => [
                'entities' => [
                    EventTriggerInterface::EVENT_CREATE => [
                        'id'        => 1,
                        'event'     => EventTriggerInterface::EVENT_CREATE,
                        'priority'  => 10,
                        'timeShift' => 10
                    ],
                    EventTriggerInterface::EVENT_UPDATE => [
                        'id'        => 2,
                        'event'     => EventTriggerInterface::EVENT_UPDATE,
                        'priority'  => 10,
                        'timeShift' => 60
                    ],
                    EventTriggerInterface::EVENT_DELETE => [
                        'id'        => 3,
                        'event'     => EventTriggerInterface::EVENT_DELETE,
                        'priority'  => 10,
                        'timeShift' => 60
                    ]
                ]
            ]
        ];
    }

    public function testProcessForceQueued()
    {
        $this->extension->setForceQueued(true);

        $this->handler->expects($this->any())->method('isTriggerApplicable')->willReturn(true);

        $expectedTrigger = $this->getTriggers('create');

        $this->prepareRepository();
        $this->prepareSchedulePolicy($expectedTrigger, true);
        $this->prepareTriggerCache(self::ENTITY_CLASS, ProcessTrigger::EVENT_CREATE);

        // persist trigger is not queued
        $this->callPreFunctionByEventName(ProcessTrigger::EVENT_CREATE, $this->getMainEntity());

        // there is no need to check all trace - just ensure that job was queued
        $this->entityManager->expects($this->exactly(1))->method('persist');
        $this->entityManager->expects($this->exactly(1))->method('flush');

        $this->handler->expects($this->never())->method('handleTrigger');

        $this->extension->process($this->entityManager);
    }

    public function testProcessRemovedEntityHashes()
    {
        $entity = $this->getMainEntity();

        $this->prepareRepository([]);
        $this->prepareTriggerCache(self::ENTITY_CLASS, ProcessTrigger::EVENT_DELETE);

        // expectations
        $expectedEntityHashes = [ProcessJob::generateEntityHash(self::ENTITY_CLASS, $entity->getId())];

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity, false)
            ->willReturn($entity->getId());

        $this->processJobRepository->expects($this->once())->method('deleteByHashes');

        // test
        $this->callPreFunctionByEventName(ProcessTrigger::EVENT_DELETE, $entity);
        $this->assertAttributeEquals($expectedEntityHashes, 'removedEntityHashes', $this->extension);

        $this->handler->expects($this->never())->method($this->anything());

        $this->extension->process($this->entityManager);

        $this->assertAttributeEmpty('removedEntityHashes', $this->extension);
    }

    /**
     * @param null|string $triggerName
     * @return ProcessTrigger[]|ProcessTrigger
     */
    protected function getTriggers($triggerName = null)
    {
        if (!$this->triggers) {
            $triggerPriority = 0;

            $definition = new ProcessDefinition();
            $definition->setName('test')->setRelatedEntity(self::ENTITY_CLASS);

            $createTrigger = new ProcessTrigger();
            $createTrigger->setDefinition($definition)
                ->setEvent(EventTriggerInterface::EVENT_CREATE)
                ->setPriority($triggerPriority);

            $updateEntityTrigger = new ProcessTrigger();
            $updateEntityTrigger->setDefinition($definition)
                ->setEvent(EventTriggerInterface::EVENT_UPDATE)
                ->setQueued(true)
                ->setPriority($triggerPriority)
                ->setTimeShift(60);

            $updateFieldTrigger = new ProcessTrigger();
            $updateFieldTrigger->setDefinition($definition)
                ->setEvent(EventTriggerInterface::EVENT_UPDATE)
                ->setPriority($triggerPriority)
                ->setField(self::FIELD);

            $deleteTrigger = new ProcessTrigger();
            $deleteTrigger->setDefinition($definition)
                ->setPriority($triggerPriority)
                ->setEvent(EventTriggerInterface::EVENT_DELETE);

            $this->triggers = [
                'create' => $createTrigger,
                'updateEntity' => $updateEntityTrigger,
                'updateField' => $updateFieldTrigger,
                'delete' => $deleteTrigger,
            ];
        }

        return $triggerName ? $this->triggers[$triggerName] : $this->triggers;
    }

    /**
     * @param array|ProcessTrigger $triggers
     * @param bool $allow
     */
    protected function prepareSchedulePolicy($triggers, $allow)
    {
        $triggers = $triggers instanceof ProcessTrigger ? [$triggers] : $triggers;
        foreach (array_values($triggers) as $index => $trigger) {
            $this->schedulePolicy->expects($this->at($index))
                ->method('isScheduleAllowed')
                ->with($trigger, $this->isInstanceOf(ProcessData::class))
                ->willReturn($allow);
        }
    }

    /**
     * @param array $data
     * @param bool $modified
     * @return ProcessData
     */
    protected function createProcessData(array $data, $modified = true)
    {
        $processData = new ProcessData($data);
        $processData->setModified($modified);

        return $processData;
    }

    /**
     * @param array $config
     * @return ProcessTrigger
     */
    protected function getCustomQueuedTrigger(array $config)
    {
        $definition = new ProcessDefinition();
        $definition->setName('test-' . uniqid('test', true))->setRelatedEntity(self::ENTITY_CLASS);

        $entityTrigger = new ProcessTrigger();
        $entityTrigger->setDefinition($definition)
            ->setEvent($config['event'])
            ->setQueued(true)
            ->setPriority($config['priority'])
            ->setTimeShift($config['timeShift']);

        return $entityTrigger;
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $entityManager
     * @param array $entityParams
     * @param int $callOrder
     */
    protected function assertProcessJobPersist($entityManager, array $entityParams, $callOrder)
    {
        $entityManager->expects($this->at($callOrder))->method('persist')
            ->with($this->isInstanceOf(ProcessJob::class))
            ->will(
                $this->returnCallback(
                    function (ProcessJob $processJob) use ($entityParams) {
                        $event = $processJob->getProcessTrigger()->getEvent();

                        $idReflection = new \ReflectionProperty(ProcessJob::class, 'id');
                        $idReflection->setAccessible(true);
                        $idReflection->setValue($processJob, $entityParams[$event]['id']);
                    }
                )
            );
    }
}
