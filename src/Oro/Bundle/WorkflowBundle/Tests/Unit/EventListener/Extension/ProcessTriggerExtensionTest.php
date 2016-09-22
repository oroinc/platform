<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener\Extension;

use Doctrine\ORM\EntityManager;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Cache\EventTriggerCache;
use Oro\Bundle\WorkflowBundle\Command\ExecuteProcessJobCommand;
use Oro\Bundle\WorkflowBundle\Entity\EventTriggerInterface;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessJobRepository;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessTriggerRepository;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Bundle\WorkflowBundle\Model\ProcessHandler;
use Oro\Bundle\WorkflowBundle\EventListener\Extension\ProcessTriggerExtension;
use Oro\Bundle\WorkflowBundle\Model\ProcessLogger;
use Oro\Bundle\WorkflowBundle\Model\ProcessSchedulePolicy;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProcessTriggerExtensionTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY = 'stdClass';
    const FIELD = 'field';

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManager */
    protected $entityManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessTriggerRepository */
    protected $repository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessJobRepository */
    protected $processJobRepository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessHandler */
    protected $handler;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessLogger */
    protected $logger;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EventTriggerCache */
    protected $triggerCache;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessSchedulePolicy */
    protected $schedulePolicy;

    /** @var ProcessTriggerExtension */
    protected $extension;

    /** @var array */
    protected $triggers;

    protected function setUp()
    {
        $this->entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();

        $this->repository = $this->getMockBuilder(ProcessTriggerRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findAllWithDefinitions'])
            ->getMock();

        $this->processJobRepository = $this->getMockBuilder(ProcessJobRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['deleteByHashes'])
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)->disableOriginalConstructor()->getMock();
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

        $this->triggerCache = $this->getMockBuilder(EventTriggerCache::class)->disableOriginalConstructor()->getMock();

        $this->schedulePolicy = $this->getMock(ProcessSchedulePolicy::class);

        $this->extension = new ProcessTriggerExtension(
            $this->doctrineHelper,
            $this->handler,
            $this->logger,
            $this->triggerCache,
            $this->schedulePolicy
        );
    }

    protected function tearDown()
    {
        unset(
            $this->extension,
            $this->doctrineHelper,
            $this->handler,
            $this->logger,
            $this->triggerCache,
            $this->schedulePolicy,
            $this->repository,
            $this->processJobRepository,
            $this->entityManager
        );
    }

    public function testSetForceQueued()
    {
        $this->assertAttributeEquals(false, 'forceQueued', $this->extension);

        $this->extension->setForceQueued(true);

        $this->assertAttributeEquals(true, 'forceQueued', $this->extension);
    }

    public function testHasTriggers()
    {
        $entityClass = self::ENTITY;
        $entity = new $entityClass();

        $this->triggerCache->expects($this->once())
            ->method('hasTrigger')
            ->with($entityClass, EventTriggerInterface::EVENT_CREATE);

        $this->extension->hasTriggers($entity, EventTriggerInterface::EVENT_CREATE);
    }

    public function testScheduleCreateEvent()
    {
        $entityClass = self::ENTITY;
        $entity = new $entityClass();

        $createTrigger = $this->getTriggers('create');

        $this->prepareRepository();
        $this->prepareSchedulePolicy($createTrigger, true);
        $this->prepareTriggerCache($entityClass, EventTriggerInterface::EVENT_CREATE);

        $this->callPreFunctionByEventName(EventTriggerInterface::EVENT_CREATE, $entity);

        $expectedTriggers = $this->getExpectedTriggers($this->getTriggers());
        $expectedScheduledProcessed = [
            self::ENTITY => [
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
        $entityClass = self::ENTITY;
        $entity = new $entityClass();

        $createTrigger = $this->getTriggers('create');

        $this->prepareRepository();
        $this->prepareSchedulePolicy($createTrigger, false);
        $this->prepareTriggerCache($entityClass, EventTriggerInterface::EVENT_CREATE);

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
        $entityClass = self::ENTITY;
        $entity      = new $entityClass();
        $oldValue    = 1;
        $newValue    = 2;
        $changeSet   = [self::FIELD => ['old' => $oldValue, 'new' => $newValue]];

        $updateEntityTrigger = $this->getTriggers('updateEntity');
        $updateFieldTrigger = $this->getTriggers('updateField');

        $this->prepareRepository();
        $this->prepareSchedulePolicy([$updateEntityTrigger, $updateFieldTrigger], true);
        $this->prepareTriggerCache($entityClass, EventTriggerInterface::EVENT_UPDATE);

        $this->callPreFunctionByEventName(EventTriggerInterface::EVENT_UPDATE, $entity, $changeSet);

        $expectedTriggers = $this->getExpectedTriggers($this->getTriggers());
        $expectedScheduledProcessed = [
            self::ENTITY => [
                [
                    'trigger' => $updateEntityTrigger,
                    'data'    => $this->createProcessData(
                        [
                            'data'      => $entity,
                            'changeSet' => [
                                'field' => [
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
                                'field' => [
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
        $entityClass = self::ENTITY;
        $entity      = new $entityClass();
        $oldValue    = 1;
        $newValue    = 2;
        $changeSet   = [self::FIELD => ['old' => $oldValue, 'new' => $newValue]];

        $updateEntityTrigger = $this->getTriggers('updateEntity');
        $updateFieldTrigger = $this->getTriggers('updateField');

        $this->prepareRepository();
        $this->prepareSchedulePolicy([$updateEntityTrigger, $updateFieldTrigger], false);
        $this->prepareTriggerCache($entityClass, EventTriggerInterface::EVENT_UPDATE);

        $this->callPreFunctionByEventName(EventTriggerInterface::EVENT_UPDATE, $entity, $changeSet);

        $expectedTriggers = $this->getExpectedTriggers($this->getTriggers());

        $this->assertAttributeEquals($expectedTriggers, 'triggers', $this->extension);
        $this->assertAttributeEmpty('scheduledProcesses', $this->extension);
    }

    public function testScheduleDeleteEvent()
    {
        $entityClass = self::ENTITY;
        $entity      = new $entityClass();
        $entityId    = 1;

        $deleteTrigger = $this->getTriggers('delete');

        $this->prepareRepository();
        $this->prepareSchedulePolicy($deleteTrigger, true);
        $this->prepareTriggerCache($entityClass, EventTriggerInterface::EVENT_DELETE);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity, false)
            ->willReturn($entityId);

        $this->callPreFunctionByEventName(EventTriggerInterface::EVENT_DELETE, $entity);

        $expectedTriggers = $this->getExpectedTriggers($this->getTriggers());
        $expectedScheduledProcessed = [
            self::ENTITY => [
                [
                    'trigger' => $deleteTrigger,
                    'data' => $this->createProcessData(['data' => $entity])
                ]
            ]
        ];
        $expectedEntityHashes = [ProcessJob::generateEntityHash($entityClass, $entityId)];

        $this->assertAttributeEquals($expectedTriggers, 'triggers', $this->extension);
        $this->assertAttributeEquals($expectedScheduledProcessed, 'scheduledProcesses', $this->extension);
        $this->assertAttributeEquals($expectedEntityHashes, 'removedEntityHashes', $this->extension);
    }

    public function testScheduleDeleteEventNotAllowSchedule()
    {
        $entityClass = self::ENTITY;
        $entity      = new $entityClass();
        $entityId    = 1;

        $deleteTrigger = $this->getTriggers('delete');

        $this->prepareRepository();
        $this->prepareSchedulePolicy($deleteTrigger, false);
        $this->prepareTriggerCache($entityClass, EventTriggerInterface::EVENT_DELETE);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity, false)
            ->willReturn($entityId);

        $this->callPreFunctionByEventName(EventTriggerInterface::EVENT_DELETE, $entity);

        $expectedTriggers = $this->getExpectedTriggers($this->getTriggers());
        $expectedEntityHashes = [ProcessJob::generateEntityHash($entityClass, $entityId)];

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
        // prepare environment
        $entityClass = self::ENTITY;

        $createTrigger = $this->getTriggers('create');

        $this->prepareRepository();
        $this->prepareSchedulePolicy($createTrigger, true);
        $this->prepareTriggerCache($entityClass, EventTriggerInterface::EVENT_CREATE);

        $expectedTriggers = $this->getExpectedTriggers($this->getTriggers());

        $this->callPreFunctionByEventName(EventTriggerInterface::EVENT_CREATE, new $entityClass());

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
                'className' => self::ENTITY,
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
        $entityClass = self::ENTITY;
        $entity = new $entityClass();

        $this->handler->expects($this->once())->method('isTriggerApplicable')->willReturn(true);

        $expectedTrigger = $this->getTriggers('create');

        $this->prepareRepository();
        $this->prepareSchedulePolicy($expectedTrigger, true);
        $this->prepareTriggerCache($entityClass, EventTriggerInterface::EVENT_CREATE);

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
        $entityClass = self::ENTITY;
        $entity = new $entityClass();

        $this->handler->expects($this->any())->method('isTriggerApplicable')->willReturn(false);

        $expectedTrigger = $this->getTriggers('create');

        $this->prepareRepository();
        $this->prepareSchedulePolicy($expectedTrigger, true);
        $this->prepareTriggerCache($entityClass, EventTriggerInterface::EVENT_CREATE);

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
     * @param array $expected
     */
    public function testProcessQueuedProcessJob($entityParams, $expected)
    {
        $callOrder    = 0;
        $entityClass  = self::ENTITY;
        $entity       = new $entityClass();
        $expectedData = $this->createProcessData(['data' => $entity]);
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

            $this->callPreFunctionByEventName($trigger->getEvent(), new $entityClass());

            $this->assertProcessJobPersist($this->entityManager, $entityParams, $iteration);
            $callOrder = $iteration;
        }

        $this->entityManager->expects($this->at(++$callOrder))->method('flush');

        foreach ($expected as $jmsParams) {
            $this->assertJMSJobPersist($this->entityManager, $jmsParams, ++$callOrder);
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
                ],
                'expected' => [
                    [
                        'commandArgs' => ['--id=1', '--id=2', '--id=3'],
                        'priority'    => 10,
                        'timeShift'   => 60
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
                ],
                'expected' => [
                    [
                        'commandArgs' => ['--id=1'],
                        'priority'    => 90,
                        'timeShift'   => 60
                    ],
                    [
                        'commandArgs' => ['--id=2', '--id=3'],
                        'priority'    => 10,
                        'timeShift'   => 60
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
                ],
                'expected' => [
                    [
                        'commandArgs' => ['--id=1'],
                        'priority'    => 10,
                        'timeShift'   => 10
                    ],
                    [
                        'commandArgs' => ['--id=2'],
                        'priority'    => 10,
                        'timeShift'   => 20
                    ],
                    [
                        'commandArgs' => ['--id=3'],
                        'priority'    => 10,
                        'timeShift'   => 30
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
                ],
                'expected' => [
                    [
                        'commandArgs' => ['--id=1'],
                        'priority'    => 10,
                        'timeShift'   => 10
                    ],
                    [
                        'commandArgs' => ['--id=2', '--id=3'],
                        'priority'    => 10,
                        'timeShift'   => 60
                    ]
                ]
            ]
        ];
    }

    public function testProcessForceQueued()
    {
        $this->extension->setForceQueued(true);

        $entityClass = self::ENTITY;
        $entity = new $entityClass();

        $this->handler->expects($this->any())->method('isTriggerApplicable')->willReturn(true);

        $expectedTrigger = $this->getTriggers('create');

        $this->prepareRepository();
        $this->prepareSchedulePolicy($expectedTrigger, true);
        $this->prepareTriggerCache($entityClass, ProcessTrigger::EVENT_CREATE);

        // persist trigger is not queued
        $this->callPreFunctionByEventName(ProcessTrigger::EVENT_CREATE, $entity);

        // there is no need to check all trace - just ensure that job was queued
        $this->entityManager->expects($this->exactly(3))->method('persist');
        $this->entityManager->expects($this->exactly(3))->method('flush');

        $this->handler->expects($this->never())->method('handleTrigger');

        $this->extension->process($this->entityManager);
    }

    public function testProcessRemovedEntityHashes()
    {
        $entityClass = self::ENTITY;
        $entity      = new $entityClass();
        $entityId    = 1;

        $this->prepareRepository([]);
        $this->prepareTriggerCache($entityClass, ProcessTrigger::EVENT_DELETE);

        // expectations
        $expectedEntityHashes = [ProcessJob::generateEntityHash($entityClass, $entityId)];

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity, false)
            ->willReturn($entityId);

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
            $definition->setName('test')->setRelatedEntity(self::ENTITY);

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
     * @param string $entityClass
     * @param string $event
     * @param bool $hasTrigger
     */
    protected function prepareTriggerCache($entityClass, $event, $hasTrigger = true)
    {
        $this->triggerCache->expects($this->any())
            ->method('hasTrigger')
            ->with($entityClass, $event)
            ->willReturn($hasTrigger);
    }

    /**
     * @param string $event
     * @param object $entity
     * @param array $changeSet
     */
    protected function callPreFunctionByEventName($event, $entity, $changeSet = [])
    {
        switch ($event) {
            case EventTriggerInterface::EVENT_CREATE:
                $this->extension->schedule($entity, $event);
                break;
            case EventTriggerInterface::EVENT_UPDATE:
                $this->extension->schedule($entity, $event, $changeSet);
                break;
            case EventTriggerInterface::EVENT_DELETE:
                $this->extension->schedule($entity, $event);
                break;
        }
    }

    /**
     * @param ProcessTrigger[] $triggers
     * @return array
     */
    protected function getExpectedTriggers(array $triggers)
    {
        $expectedTriggers = [];

        foreach ($triggers as $trigger) {
            $entityClass = $trigger->getDefinition()->getRelatedEntity();
            $event = $trigger->getEvent();
            $field = $trigger->getField();

            if ($event === EventTriggerInterface::EVENT_UPDATE) {
                if ($field) {
                    $expectedTriggers[$entityClass][$event]['field'][$field][] = $trigger;
                } else {
                    $expectedTriggers[$entityClass][$event]['entity'][] = $trigger;
                }
            } else {
                $expectedTriggers[$entityClass][$event][] = $trigger;
            }
        }

        return $expectedTriggers;
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
        $definition->setName('test-' . uniqid('test', true))->setRelatedEntity(self::ENTITY);

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
            ->with($this->isInstanceOf('Oro\Bundle\WorkflowBundle\Entity\ProcessJob'))
            ->will(
                $this->returnCallback(
                    function (ProcessJob $processJob) use ($entityParams) {
                        $event = $processJob->getProcessTrigger()->getEvent();

                        $idReflection = new \ReflectionProperty('Oro\Bundle\WorkflowBundle\Entity\ProcessJob', 'id');
                        $idReflection->setAccessible(true);
                        $idReflection->setValue($processJob, $entityParams[$event]['id']);
                    }
                )
            );
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $entityManager
     * @param array $expected
     * @param int $callOrder
     */
    protected function assertJMSJobPersist($entityManager, array $expected, $callOrder)
    {
        $entityManager->expects($this->at($callOrder))->method('persist')
            ->with($this->isInstanceOf('JMS\JobQueueBundle\Entity\Job'))
            ->will(
                $this->returnCallback(
                    function (Job $jmsJob) use ($expected) {
                        $this->assertEquals(ExecuteProcessJobCommand::NAME, $jmsJob->getCommand());
                        $this->assertEquals($expected['commandArgs'], $jmsJob->getArgs());
                        $this->assertEquals($expected['priority'], $jmsJob->getPriority());

                        $timeShiftInterval = ProcessTrigger::convertSecondsToDateInterval($expected['timeShift']);
                        $executeAfter = new \DateTime('now', new \DateTimeZone('UTC'));
                        $executeAfter->add($timeShiftInterval);

                        $this->assertLessThanOrEqual($executeAfter, $jmsJob->getExecuteAfter());
                    }
                )
            );
    }

    /**
     * @param array $triggers
     */
    protected function prepareRepository(array $triggers = null)
    {
        $this->repository->expects($this->once())
            ->method('findAllWithDefinitions')
            ->with(true)
            ->willReturn($triggers ?: $this->getTriggers());
    }
}
