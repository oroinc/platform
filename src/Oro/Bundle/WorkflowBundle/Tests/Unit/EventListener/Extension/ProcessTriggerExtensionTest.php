<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener\Extension;

use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Bundle\WorkflowBundle\Async\Topics;
use Oro\Bundle\WorkflowBundle\Entity\EventTriggerInterface;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessJobRepository;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessTriggerRepository;
use Oro\Bundle\WorkflowBundle\EventListener\Extension\AbstractEventTriggerExtension;
use Oro\Bundle\WorkflowBundle\EventListener\Extension\ProcessTriggerExtension;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Bundle\WorkflowBundle\Model\ProcessHandler;
use Oro\Bundle\WorkflowBundle\Model\ProcessLogger;
use Oro\Bundle\WorkflowBundle\Model\ProcessSchedulePolicy;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\Testing\ReflectionUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProcessTriggerExtensionTest extends AbstractEventTriggerExtensionTestCase
{
    use MessageQueueExtension;

    /** @var ProcessTriggerRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $repository;

    /** @var ProcessJobRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $processJobRepository;

    /** @var ProcessHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $handler;

    /** @var ProcessLogger|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ProcessSchedulePolicy|\PHPUnit\Framework\MockObject\MockObject */
    private $schedulePolicy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(ProcessTriggerRepository::class);
        $this->processJobRepository = $this->createMock(ProcessJobRepository::class);
        $this->handler = $this->createMock(ProcessHandler::class);
        $this->logger = $this->createMock(ProcessLogger::class);
        $this->schedulePolicy = $this->createMock(ProcessSchedulePolicy::class);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->willReturnMap([
                [ProcessTrigger::class, $this->repository],
                [ProcessJob::class, $this->processJobRepository]
            ]);

        $this->extension = new ProcessTriggerExtension(
            $this->doctrineHelper,
            $this->handler,
            $this->logger,
            $this->triggerCache,
            $this->schedulePolicy,
            self::getMessageProducer()
        );
    }

    public function testSetForceQueued()
    {
        self::assertFalse(ReflectionUtil::getPropertyValue($this->extension, 'forceQueued'));

        $this->extension->setForceQueued(true);
        self::assertTrue(ReflectionUtil::getPropertyValue($this->extension, 'forceQueued'));
    }

    public function testHasTriggers()
    {
        $this->triggerCache->expects(self::once())
            ->method('hasTrigger')
            ->with(self::ENTITY_CLASS, EventTriggerInterface::EVENT_CREATE);

        $this->extension->hasTriggers($this->getMainEntity(), EventTriggerInterface::EVENT_CREATE);
    }

    public function testScheduleCreateEvent()
    {
        $entity = $this->getMainEntity();

        $createTrigger = $this->getTriggers('create');

        $this->prepareRepository();
        $this->prepareTriggerCache(self::ENTITY_CLASS, EventTriggerInterface::EVENT_CREATE);

        $this->schedulePolicy->expects($this->once())
            ->method('isScheduleAllowed')
            ->with($createTrigger, $this->isInstanceOf(ProcessData::class))
            ->willReturn(true);

        $this->callPreFunctionByEventName(EventTriggerInterface::EVENT_CREATE, $entity, [], $this->extension);

        $expectedTriggers = $this->getExpectedTriggers($this->getTriggers());
        $expectedScheduledProcessed = [
            self::ENTITY_CLASS => [
                [
                    'trigger' => $createTrigger,
                    'data' => $this->createProcessData(['data' => $entity])
                ]
            ]
        ];

        self::assertEquals($expectedTriggers, $this->getExtensionTriggers($this->extension));
        self::assertEquals($expectedScheduledProcessed, $this->getExtensionScheduledProcesses($this->extension));
    }

    public function testScheduleCreateEventNotAllowSchedule()
    {
        $entity = $this->getMainEntity();

        $createTrigger = $this->getTriggers('create');

        $this->prepareRepository();
        $this->prepareTriggerCache(self::ENTITY_CLASS, EventTriggerInterface::EVENT_CREATE);

        $this->schedulePolicy->expects($this->once())
            ->method('isScheduleAllowed')
            ->with($createTrigger, $this->isInstanceOf(ProcessData::class))
            ->willReturn(false);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                'Policy declined process scheduling',
                $createTrigger,
                $this->isInstanceOf(ProcessData::class)
            );

        $this->callPreFunctionByEventName(EventTriggerInterface::EVENT_CREATE, $entity, [], $this->extension);

        $expectedTriggers = $this->getExpectedTriggers($this->getTriggers());

        self::assertEquals($expectedTriggers, $this->getExtensionTriggers($this->extension));
        self::assertEmpty($this->getExtensionScheduledProcesses($this->extension));
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
        $this->prepareTriggerCache(self::ENTITY_CLASS, EventTriggerInterface::EVENT_UPDATE);

        $this->schedulePolicy->expects($this->exactly(2))
            ->method('isScheduleAllowed')
            ->withConsecutive(
                [$updateEntityTrigger, $this->isInstanceOf(ProcessData::class)],
                [$updateFieldTrigger, $this->isInstanceOf(ProcessData::class)]
            )
            ->willReturn(true);

        $this->callPreFunctionByEventName(EventTriggerInterface::EVENT_UPDATE, $entity, $changeSet, $this->extension);

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

        self::assertEquals($expectedTriggers, $this->getExtensionTriggers($this->extension));
        self::assertEquals($expectedScheduledProcessed, $this->getExtensionScheduledProcesses($this->extension));
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
        $this->prepareTriggerCache(self::ENTITY_CLASS, EventTriggerInterface::EVENT_UPDATE);

        $this->schedulePolicy->expects($this->exactly(2))
            ->method('isScheduleAllowed')
            ->withConsecutive(
                [$updateEntityTrigger, $this->isInstanceOf(ProcessData::class)],
                [$updateFieldTrigger, $this->isInstanceOf(ProcessData::class)]
            )
            ->willReturn(false);

        $this->callPreFunctionByEventName(EventTriggerInterface::EVENT_UPDATE, $entity, $changeSet, $this->extension);

        $expectedTriggers = $this->getExpectedTriggers($this->getTriggers());

        self::assertEquals($expectedTriggers, $this->getExtensionTriggers($this->extension));
        self::assertEmpty($this->getExtensionScheduledProcesses($this->extension));
    }

    public function testScheduleDeleteEvent()
    {
        $entity = $this->getMainEntity();

        $deleteTrigger = $this->getTriggers('delete');

        $this->prepareRepository();
        $this->prepareTriggerCache(self::ENTITY_CLASS, EventTriggerInterface::EVENT_DELETE);

        $this->schedulePolicy->expects($this->once())
            ->method('isScheduleAllowed')
            ->with($deleteTrigger, $this->isInstanceOf(ProcessData::class))
            ->willReturn(true);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity, false)
            ->willReturn($entity->getId());

        $this->callPreFunctionByEventName(EventTriggerInterface::EVENT_DELETE, $entity, [], $this->extension);

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

        self::assertEquals($expectedTriggers, $this->getExtensionTriggers($this->extension));
        self::assertEquals($expectedScheduledProcessed, $this->getExtensionScheduledProcesses($this->extension));
        self::assertEquals($expectedEntityHashes, $this->getExtensionRemovedEntityHashes($this->extension));
    }

    public function testScheduleDeleteEventNotAllowSchedule()
    {
        $entity = $this->getMainEntity();

        $deleteTrigger = $this->getTriggers('delete');

        $this->prepareRepository();
        $this->prepareTriggerCache(self::ENTITY_CLASS, EventTriggerInterface::EVENT_DELETE);

        $this->schedulePolicy->expects($this->once())
            ->method('isScheduleAllowed')
            ->with($deleteTrigger, $this->isInstanceOf(ProcessData::class))
            ->willReturn(false);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity, false)
            ->willReturn($entity->getId());

        $this->callPreFunctionByEventName(EventTriggerInterface::EVENT_DELETE, $entity, [], $this->extension);

        $expectedTriggers = $this->getExpectedTriggers($this->getTriggers());
        $expectedEntityHashes = [ProcessJob::generateEntityHash(self::ENTITY_CLASS, $entity->getId())];

        self::assertEquals($expectedTriggers, $this->getExtensionTriggers($this->extension));
        self::assertEmpty($this->getExtensionScheduledProcesses($this->extension));
        self::assertEquals($expectedEntityHashes, $this->getExtensionRemovedEntityHashes($this->extension));
    }

    /**
     * @dataProvider clearDataProvider
     */
    public function testClear(?string $className, bool $hasScheduledProcesses)
    {
        $createTrigger = $this->getTriggers('create');

        $this->prepareRepository();
        $this->prepareTriggerCache(self::ENTITY_CLASS, EventTriggerInterface::EVENT_CREATE);

        $this->schedulePolicy->expects($this->once())
            ->method('isScheduleAllowed')
            ->with($createTrigger, $this->isInstanceOf(ProcessData::class))
            ->willReturn(true);

        $expectedTriggers = $this->getExpectedTriggers($this->getTriggers());

        $this->callPreFunctionByEventName(
            EventTriggerInterface::EVENT_CREATE,
            $this->getMainEntity(),
            [],
            $this->extension
        );

        self::assertEquals($expectedTriggers, $this->getExtensionTriggers($this->extension));
        self::assertNotEmpty($this->getExtensionScheduledProcesses($this->extension));

        // test
        $this->extension->clear($className);

        self::assertNull($this->getExtensionTriggers($this->extension));

        if ($hasScheduledProcesses) {
            self::assertNotEmpty($this->getExtensionScheduledProcesses($this->extension));
        } else {
            self::assertEmpty($this->getExtensionScheduledProcesses($this->extension));
        }
    }

    public function clearDataProvider(): array
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

        $this->handler->expects($this->once())
            ->method('isTriggerApplicable')
            ->willReturn(true);

        $expectedTrigger = $this->getTriggers('create');

        $this->prepareRepository();
        $this->prepareTriggerCache(self::ENTITY_CLASS, EventTriggerInterface::EVENT_CREATE);

        $this->schedulePolicy->expects($this->once())
            ->method('isScheduleAllowed')
            ->with($expectedTrigger, $this->isInstanceOf(ProcessData::class))
            ->willReturn(true);

        $this->callPreFunctionByEventName(EventTriggerInterface::EVENT_CREATE, $entity);

        $expectedData = $this->createProcessData(['data' => $entity]);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Process handled', $expectedTrigger, $expectedData);

        $this->handler->expects($this->once())
            ->method('handleTrigger')
            ->with($expectedTrigger, $expectedData);
        $this->handler->expects($this->once())
            ->method('finishTrigger')
            ->with($expectedTrigger, $expectedData);

        $this->extension->process($this->entityManager);

        self::assertMessagesEmpty(Topics::EXECUTE_PROCESS_JOB);
    }

    public function testProcessHandledProcessUnmetPreConditions()
    {
        $entity = $this->getMainEntity();

        $this->handler->expects($this->any())
            ->method('isTriggerApplicable')
            ->willReturn(false);

        $expectedTrigger = $this->getTriggers('create');

        $this->prepareRepository();
        $this->prepareTriggerCache(self::ENTITY_CLASS, EventTriggerInterface::EVENT_CREATE);

        $this->schedulePolicy->expects($this->once())
            ->method('isScheduleAllowed')
            ->with($expectedTrigger, $this->isInstanceOf(ProcessData::class))
            ->willReturn(true);

        $this->callPreFunctionByEventName(EventTriggerInterface::EVENT_CREATE, $entity);

        $expectedData = $this->createProcessData(['data' => $entity]);

        $this->entityManager->expects($this->never())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Process trigger is not applicable', $expectedTrigger, $expectedData);

        $this->handler->expects($this->never())
            ->method('handleTrigger')
            ->with($expectedTrigger, $expectedData);
        $this->handler->expects($this->never())
            ->method('finishTrigger')
            ->with($expectedTrigger, $expectedData);

        $this->extension->process($this->entityManager);

        self::assertMessagesEmpty(Topics::EXECUTE_PROCESS_JOB);
    }

    /**
     * @dataProvider postFlushQueuedProcessJobProvider
     */
    public function testProcessQueuedProcessJob(array $entityParams)
    {
        $expectedData = $this->createProcessData(['data' => $this->getMainEntity()]);

        $this->handler->expects($this->any())
            ->method('isTriggerApplicable')
            ->willReturn(true);

        /** @var ProcessTrigger[] $triggers */
        $triggers = [];
        $isScheduleAllowedParameters = [];
        $loggerParameters = [];
        foreach ($entityParams as $entityParam) {
            $trigger = $this->getCustomQueuedTrigger($entityParam);
            $triggers[] = $trigger;
            $isScheduleAllowedParameters[] = [$trigger, $this->isInstanceOf(ProcessData::class)];
            $loggerParameters[] = ['Process queued', $trigger, $expectedData];
        }

        $this->prepareRepository($triggers);

        $this->schedulePolicy->expects($this->exactly(count($isScheduleAllowedParameters)))
            ->method('isScheduleAllowed')
            ->withConsecutive(...$isScheduleAllowedParameters)
            ->willReturn(true);

        $this->logger->expects($this->exactly(count($loggerParameters)))
            ->method('debug')
            ->withConsecutive(...$loggerParameters);

        $this->entityManager->expects($this->exactly(count($triggers)))
            ->method('persist')
            ->with($this->isInstanceOf(ProcessJob::class))
            ->willReturnCallback(function (ProcessJob $processJob) use ($entityParams) {
                $event = $processJob->getProcessTrigger()->getEvent();
                ReflectionUtil::setId($processJob, $entityParams[$event]['id']);
            });

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->handler->expects($this->never())
            ->method('handleTrigger');

        foreach ($triggers as $trigger) {
            $this->callPreFunctionByEventName($trigger->getEvent(), $this->getMainEntity());
        }

        $this->extension->process($this->entityManager);

        self::assertMessagesCount(Topics::EXECUTE_PROCESS_JOB, 3);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function postFlushQueuedProcessJobProvider(): array
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

        $this->handler->expects($this->any())
            ->method('isTriggerApplicable')
            ->willReturn(true);

        $expectedTrigger = $this->getTriggers('create');

        $this->prepareRepository();
        $this->prepareTriggerCache(self::ENTITY_CLASS, ProcessTrigger::EVENT_CREATE);

        $this->schedulePolicy->expects($this->once())
            ->method('isScheduleAllowed')
            ->with($expectedTrigger, $this->isInstanceOf(ProcessData::class))
            ->willReturn(true);

        // persist trigger is not queued
        $this->callPreFunctionByEventName(ProcessTrigger::EVENT_CREATE, $this->getMainEntity());

        // there is no need to check all trace - just ensure that job was queued
        $this->entityManager->expects($this->once())
            ->method('persist');
        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->handler->expects($this->never())
            ->method('handleTrigger');

        $this->extension->process($this->entityManager);

        self::assertMessageSentWithPriority(Topics::EXECUTE_PROCESS_JOB, MessagePriority::NORMAL);
    }

    public function testShouldSendQueuedJobOnlyAfterFlushFinished()
    {
        $entity = $this->getMainEntity();
        $triggerConfig = [
            'id'        => 123,
            'event'     => EventTriggerInterface::EVENT_UPDATE,
            'priority'  => 10,
            'timeShift' => 60
        ];
        $changeSet = ['field' => ['old value', 'new value']];

        $expectedMessage = new Message(['process_job_id' => $triggerConfig['id']], MessagePriority::HIGH);
        $expectedMessage->setDelay($triggerConfig['timeShift']);

        $expectedProcessData = $this->createProcessData([
            'data'      => $entity,
            'changeSet' => $changeSet
        ]);

        $trigger = $this->getCustomQueuedTrigger($triggerConfig);

        $this->handler->expects($this->any())
            ->method('isTriggerApplicable')
            ->willReturn(true);

        $this->prepareRepository([$trigger]);

        $this->schedulePolicy->expects($this->once())
            ->method('isScheduleAllowed')
            ->with($trigger, $this->isInstanceOf(ProcessData::class))
            ->willReturn(true);

        /** @var ProcessJob $createdProcessJob */
        $createdProcessJob = null;
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(ProcessJob::class))
            ->willReturnCallback(function (ProcessJob $processJob) use (&$createdProcessJob) {
                $createdProcessJob = $processJob;
            });
        $this->entityManager->expects($this->once())
            ->method('flush')
            ->willReturnCallback(function () use (&$createdProcessJob, $triggerConfig) {
                // emulate triggering of postFlush event during the call of flush method in the process method
                $this->extension->process($this->entityManager);
                ReflectionUtil::setId($createdProcessJob, $triggerConfig['id']);
            });

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                'Process queued',
                self::isInstanceOf(ProcessTrigger::class),
                $expectedProcessData
            );

        $this->extension->schedule($entity, $trigger->getEvent(), $changeSet);
        $this->extension->process($this->entityManager);

        self::assertEquals($changeSet, $createdProcessJob->getData()->get('changeSet'));

        self::assertMessageSent(Topics::EXECUTE_PROCESS_JOB, $expectedMessage->getBody());
        self::assertMessageSentWithPriority(Topics::EXECUTE_PROCESS_JOB, $expectedMessage->getPriority());

        $message = self::getSentMessage(Topics::EXECUTE_PROCESS_JOB, false);
        self::assertEquals($expectedMessage->getDelay(), $message->getDelay());
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

        $this->processJobRepository->expects($this->once())
            ->method('deleteByHashes');

        // test
        $this->callPreFunctionByEventName(ProcessTrigger::EVENT_DELETE, $entity, [], $this->extension);
        self::assertEquals($expectedEntityHashes, $this->getExtensionRemovedEntityHashes($this->extension));

        $this->handler->expects($this->never())
            ->method($this->anything());

        $this->extension->process($this->entityManager);

        self::assertEmpty($this->getExtensionRemovedEntityHashes($this->extension));
        self::assertMessagesEmpty(Topics::EXECUTE_PROCESS_JOB);
    }

    /**
     * {@inheritDoc}
     */
    protected function getTriggers(string $triggerName = null): array|object
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

    private function createProcessData(array $data, bool $modified = true): ProcessData
    {
        $processData = new ProcessData($data);
        $processData->setModified($modified);

        return $processData;
    }

    private function getCustomQueuedTrigger(array $config): ProcessTrigger
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

    private function getExtensionTriggers(AbstractEventTriggerExtension $extension): mixed
    {
        return ReflectionUtil::getPropertyValue($extension, 'triggers');
    }

    private function getExtensionScheduledProcesses(AbstractEventTriggerExtension $extension): mixed
    {
        return ReflectionUtil::getPropertyValue($extension, 'scheduledProcesses');
    }

    private function getExtensionRemovedEntityHashes(AbstractEventTriggerExtension $extension): mixed
    {
        return ReflectionUtil::getPropertyValue($extension, 'removedEntityHashes');
    }
}
