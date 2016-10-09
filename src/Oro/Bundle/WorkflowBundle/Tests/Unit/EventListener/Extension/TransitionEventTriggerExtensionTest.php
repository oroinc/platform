<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener\Extension;

use Oro\Bundle\WorkflowBundle\Async\TransitionTriggerMessage;
use Oro\Bundle\WorkflowBundle\Async\TransitionTriggerProcessor;
use Oro\Bundle\WorkflowBundle\Entity\EventTriggerInterface;
use Oro\Bundle\WorkflowBundle\Entity\Repository\TransitionEventTriggerRepository;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\EventListener\Extension\TransitionEventTriggerExtension;
use Oro\Bundle\WorkflowBundle\Handler\TransitionEventTriggerHandler;
use Oro\Bundle\WorkflowBundle\Helper\TransitionEventTriggerHelper;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class TransitionEventTriggerExtensionTest extends AbstractEventTriggerExtensionTest
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|TransitionEventTriggerRepository */
    protected $repository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|MessageProducerInterface */
    protected $producer;

    /** @var \PHPUnit_Framework_MockObject_MockObject|TransitionEventTriggerHelper */
    protected $helper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|TransitionEventTriggerHandler */
    protected $handler;

    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->getMockBuilder(TransitionEventTriggerRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findAllWithDefinitions'])
            ->getMock();

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->with(TransitionEventTrigger::class)
            ->willReturn($this->repository);

        $this->producer = $this->getMock(MessageProducerInterface::class);

        $this->helper = $this->getMockBuilder(TransitionEventTriggerHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = $this->getMockBuilder(TransitionEventTriggerHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new TransitionEventTriggerExtension(
            $this->doctrineHelper,
            $this->triggerCache,
            $this->producer,
            $this->helper,
            $this->handler
        );
    }

    protected function tearDown()
    {
        parent::tearDown();

        unset($this->producer, $this->helper, $this->handler, $this->repository);
    }

    /**
     * @dataProvider scheduleDataProvider
     *
     * @param string $event
     * @param array $triggers
     * @param array $changeSet
     */
    public function testScheduleCreateEvent($event, array $triggers, array $changeSet = [])
    {
        $entity = $this->getMainEntity();

        $this->prepareRepository();
        $this->prepareTriggerCache(self::ENTITY_CLASS, $event);

        $triggers = array_map(
            function ($name) {
                return $this->getTriggers($name);
            },
            $triggers
        );

        $this->helper->expects($this->exactly(count($triggers)))
            ->method('isRequirePass')
            ->willReturnMap(
                array_map(
                    function (EventTriggerInterface $trigger) use ($entity) {
                        return [$trigger, $entity, true];
                    },
                    $triggers
                )
            );

        $this->callPreFunctionByEventName($event, $entity, $changeSet);

        $expectedTriggers = $this->getExpectedTriggers($this->getTriggers());
        $expectedSchedules = $this->getExpectedSchedules($triggers);

        $this->assertAttributeEquals($expectedTriggers, 'triggers', $this->extension);
        $this->assertAttributeEquals($expectedSchedules, 'scheduled', $this->extension);
    }

    /**
     * @return array
     */
    public function scheduleDataProvider()
    {
        return [
            [
                'event' => EventTriggerInterface::EVENT_CREATE,
                'triggers' => ['create']
            ],
            [
                'event' => EventTriggerInterface::EVENT_UPDATE,
                'triggers' => ['updateEntity', 'updateField'],
                'changeSet' => [self::FIELD => ['old' => 1, 'new' => 2]]
            ],
            [
                'event' => EventTriggerInterface::EVENT_UPDATE,
                'triggers' => ['updateEntity'],
                'changeSet' => [self::FIELD => ['old' => 2, 'new' => 2]]
            ],
            [
                'event' => EventTriggerInterface::EVENT_DELETE,
                'triggers' => ['delete']
            ]
        ];
    }

    public function testScheduleRequireNotPass()
    {
        $this->prepareRepository();
        $this->prepareTriggerCache(self::ENTITY_CLASS, EventTriggerInterface::EVENT_CREATE);

        $this->helper->expects($this->any())->method('isRequirePass')->willReturn(false);

        $this->callPreFunctionByEventName(EventTriggerInterface::EVENT_CREATE, $this->getMainEntity());

        $expectedTriggers = $this->getExpectedTriggers($this->getTriggers());

        $this->assertAttributeEquals($expectedTriggers, 'triggers', $this->extension);
        $this->assertAttributeEmpty('scheduled', $this->extension);
    }

    /**
     * @dataProvider clearDataProvider
     *
     * @param string $className
     * @param bool $hasScheduled
     */
    public function testClear($className, $hasScheduled)
    {
        $this->prepareRepository();
        $this->prepareTriggerCache(self::ENTITY_CLASS, EventTriggerInterface::EVENT_CREATE);

        $this->helper->expects($this->any())->method('isRequirePass')->willReturn(true);

        $this->callPreFunctionByEventName(EventTriggerInterface::EVENT_CREATE, $this->getMainEntity());

        $this->assertAttributeEquals($this->getExpectedTriggers($this->getTriggers()), 'triggers', $this->extension);
        $this->assertAttributeNotEmpty('scheduled', $this->extension);

        // test
        $this->extension->clear($className);

        $this->assertAttributeEquals(null, 'triggers', $this->extension);

        if ($hasScheduled) {
            $this->assertAttributeNotEmpty('scheduled', $this->extension);
        } else {
            $this->assertAttributeEmpty('scheduled', $this->extension);
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
            'clear event triggers' => [
                'className' => TransitionEventTrigger::class,
                'hasScheduledProcesses' => true
            ]
        ];
    }

    public function testProcessNotQueued()
    {
        $entityClass = self::ENTITY_CLASS;
        $entity = $this->getMainEntity(null);

        $mainEntity = $this->getMainEntity();

        /** @var TransitionEventTrigger $expectedTrigger */
        $expectedTrigger = $this->getTriggers('create');
        $expectedTrigger->setQueued(false)->setTransitionName('test_transition');

        $this->prepareRepository();
        $this->prepareTriggerCache($entityClass, EventTriggerInterface::EVENT_CREATE);

        $this->helper->expects($this->any())->method('isRequirePass')->willReturn(true);
        $this->helper->expects($this->once())
            ->method('getMainEntity')
            ->with($expectedTrigger, $entity)
            ->willReturn($mainEntity);

        $this->producer->expects($this->never())->method($this->anything());

        $this->handler->expects($this->once())
            ->method('process')
            ->with($expectedTrigger, TransitionTriggerMessage::create($expectedTrigger, null))
            ->willReturn(true);

        $this->callPreFunctionByEventName(EventTriggerInterface::EVENT_CREATE, $entity);

        $this->extension->process($this->entityManager);
    }

    public function testProcessWithoutMainEntity()
    {
        $this->prepareRepository();
        $this->prepareTriggerCache(self::ENTITY_CLASS, EventTriggerInterface::EVENT_CREATE);

        $this->helper->expects($this->any())->method('isRequirePass')->willReturn(true);
        $this->helper->expects($this->any())->method('getMainEntity')->willReturn(null);

        $this->handler->expects($this->never())->method($this->anything());
        $this->producer->expects($this->never())->method($this->anything());

        $this->callPreFunctionByEventName(EventTriggerInterface::EVENT_CREATE, $this->getMainEntity());

        $this->extension->process($this->entityManager);
    }

    /**
     * @dataProvider processQueuedProvider
     *
     * @param bool $triggerQueued
     * @param bool $forceQueued
     */
    public function testProcessQueued($triggerQueued, $forceQueued)
    {
        $entity = $this->getMainEntity();

        /** @var TransitionEventTrigger $expectedTrigger */
        $expectedTrigger = $this->getTriggers('create');
        $expectedTrigger->setQueued($triggerQueued);

        $this->prepareRepository();
        $this->prepareTriggerCache(self::ENTITY_CLASS, EventTriggerInterface::EVENT_CREATE);

        $this->helper->expects($this->any())->method('isRequirePass')->willReturn(true);
        $this->helper->expects($this->once())
            ->method('getMainEntity')
            ->with($expectedTrigger, $entity)
            ->willReturn($entity);

        $this->handler->expects($this->never())->method($this->anything());

        $this->producer->expects($this->once())
            ->method('send')
            ->with(
                TransitionTriggerProcessor::EVENT_TOPIC_NAME,
                [
                    TransitionTriggerMessage::TRANSITION_TRIGGER => $expectedTrigger->getId(),
                    TransitionTriggerMessage::MAIN_ENTITY => null
                ]
            );

        $this->callPreFunctionByEventName(EventTriggerInterface::EVENT_CREATE, $entity);

        $this->extension->setForceQueued($forceQueued);
        $this->extension->process($this->entityManager);
    }

    /**
     * @return array
     */
    public function processQueuedProvider()
    {
        return [
            'queued' => [
                'triggerQueued' => true,
                'forceQueued' => false
            ],
            'force queued' => [
                'triggerQueued' => false,
                'forceQueued' => true
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getTriggers($triggerName = null)
    {
        if (!$this->triggers) {
            $priority = 0;

            $definition = new WorkflowDefinition();
            $definition->setName('test')->setRelatedEntity(self::ENTITY_CLASS)->setPriority($priority);

            $createTrigger = $this->getEntity(
                TransitionEventTrigger::class,
                [
                    'id' => 42,
                    'workflowDefinition' => $definition,
                    'event' => EventTriggerInterface::EVENT_CREATE,
                    'transitionName' => 'test_transition'
                ]
            );

            $updateEntityTrigger = new TransitionEventTrigger();
            $updateEntityTrigger->setWorkflowDefinition($definition)
                ->setEvent(EventTriggerInterface::EVENT_UPDATE)
                ->setQueued(true);

            $updateFieldTrigger = new TransitionEventTrigger();
            $updateFieldTrigger->setWorkflowDefinition($definition)
                ->setEvent(EventTriggerInterface::EVENT_UPDATE)
                ->setField(self::FIELD);

            $deleteTrigger = new TransitionEventTrigger();
            $deleteTrigger->setWorkflowDefinition($definition)->setEvent(EventTriggerInterface::EVENT_DELETE);

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
     * @param TransitionEventTrigger[] $triggers
     * @return array
     */
    protected function getExpectedSchedules(array $triggers)
    {
        $expected = [];

        foreach ($triggers as $trigger) {
            $entityClass = $trigger->getEntityClass();

            $expected[$entityClass][] = ['trigger' => $trigger, 'entity' => $this->getMainEntity()];
        }

        return $expected;
    }
}
