<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\EventListener\ProcessCollectorListener;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Bundle\WorkflowBundle\Command\ExecuteProcessJobCommand;

class ProcessCollectorListenerTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY = 'stdClass';
    const FIELD  = 'field';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $handler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var ProcessCollectorListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\ProcessHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\ProcessLogger')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new ProcessCollectorListener(
            $this->registry,
            $this->doctrineHelper,
            $this->handler,
            $this->logger
        );
    }

    public function testPrePersist()
    {
        $triggers = $this->getTriggers();
        $this->prepareRegistry($triggers);

        $entityClass = self::ENTITY;
        $entity = new $entityClass();
        $this->callPreFunctionByEventName(ProcessTrigger::EVENT_CREATE, $entity, $this->getEntityManager());

        $expectedTriggers = $this->getExpectedTriggers($triggers);
        $expectedScheduledProcessed = array(
            self::ENTITY => array(
                array(
                    'trigger' => $triggers['create'],
                    'data' => $this->createProcessData(array('data' => $entity))
                )
            )
        );

        $this->assertAttributeEquals($expectedTriggers, 'triggers', $this->listener);
        $this->assertAttributeEquals($expectedScheduledProcessed, 'scheduledProcesses', $this->listener);
    }

    public function testPreUpdate()
    {
        $triggers = $this->getTriggers();
        $this->prepareRegistry($triggers);

        $entityClass = self::ENTITY;
        $entity      = new $entityClass();
        $oldValue    = 1;
        $newValue    = 2;
        $changeSet   = array(self::FIELD => array($oldValue, $newValue));

        $this->callPreFunctionByEventName(ProcessTrigger::EVENT_UPDATE, $entity, $this->getEntityManager(), $changeSet);

        $expectedTriggers = $this->getExpectedTriggers($triggers);
        $expectedScheduledProcessed = array(
            self::ENTITY => array(
                array(
                    'trigger' => $triggers['updateEntity'],
                    'data' => $this->createProcessData(array('data' => $entity))
                ),
                array(
                    'trigger' => $triggers['updateField'],
                    'data' => $this->createProcessData(
                        array('data' => $entity, 'old' => $oldValue, 'new' => $newValue)
                    )
                ),
            ),
        );

        $this->assertAttributeEquals($expectedTriggers, 'triggers', $this->listener);
        $this->assertAttributeEquals($expectedScheduledProcessed, 'scheduledProcesses', $this->listener);
    }

    public function testPreRemove()
    {
        $triggers = $this->getTriggers();
        $this->prepareRegistry($triggers);

        $entityClass = self::ENTITY;
        $entity      = new $entityClass();
        $entityId    = 1;

        $this->doctrineHelper->expects($this->once())->method('getSingleEntityIdentifier')->with($entity, false)
            ->will($this->returnValue($entityId));

        $this->callPreFunctionByEventName(ProcessTrigger::EVENT_DELETE, $entity, $this->getEntityManager());

        $expectedTriggers = $this->getExpectedTriggers($triggers);
        $expectedScheduledProcessed = array(
            self::ENTITY => array(
                array(
                    'trigger' => $triggers['delete'],
                    'data' => $this->createProcessData(array('data' => $entity))
                )
            )
        );
        $expectedEntityHashes = array(ProcessJob::generateEntityHash($entityClass, $entityId));

        $this->assertAttributeEquals($expectedTriggers, 'triggers', $this->listener);
        $this->assertAttributeEquals($expectedScheduledProcessed, 'scheduledProcesses', $this->listener);
        $this->assertAttributeEquals($expectedEntityHashes, 'removedEntityHashes', $this->listener);
    }

    /**
     * @param OnClearEventArgs $args
     * @param bool $hasTriggers
     * @param bool $hasScheduledProcesses
     * @dataProvider onClearDataProvider
     */
    public function testOnClear(OnClearEventArgs $args, $hasTriggers, $hasScheduledProcesses)
    {
        $triggers = $this->getTriggers();
        $this->prepareRegistry($triggers);
        $expectedTriggers = $this->getExpectedTriggers($triggers);

        // prepare environment
        $entityClass = self::ENTITY;
        $this->callPreFunctionByEventName(ProcessTrigger::EVENT_CREATE, new $entityClass(), $this->getEntityManager());

        $this->assertAttributeEquals($expectedTriggers, 'triggers', $this->listener);
        $this->assertAttributeNotEmpty('scheduledProcesses', $this->listener);

        // test
        $this->listener->onClear($args);

        if ($hasTriggers) {
            $this->assertAttributeEquals($expectedTriggers, 'triggers', $this->listener);
        } else {
            $this->assertAttributeEquals(null, 'triggers', $this->listener);
        }
        if ($hasScheduledProcesses) {
            $this->assertAttributeNotEmpty('scheduledProcesses', $this->listener);
        } else {
            $this->assertAttributeEmpty('scheduledProcesses', $this->listener);
        }
    }

    /**
     * @return array
     */
    public function onClearDataProvider()
    {
        return array(
            'clear all' => array(
                'args' => new OnClearEventArgs($this->getEntityManager()),
                'hasTriggers' => false,
                'hasScheduledProcesses' => false,
            ),
            'clear scheduled processes' => array(
                'args' => new OnClearEventArgs($this->getEntityManager(), self::ENTITY),
                'hasTriggers' => true,
                'hasScheduledProcesses' => false,
            ),
            'clear process triggers' => array(
                'args' => new OnClearEventArgs(
                    $this->getEntityManager(),
                    'Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger'
                ),
                'hasTriggers' => false,
                'hasScheduledProcesses' => true,
            )
        );
    }

    public function testPostFlushHandledProcess()
    {
        $triggers = $this->getTriggers();
        $this->prepareRegistry($triggers);
        $entityManager = $this->getEntityManager();

        $entityClass = self::ENTITY;
        $entity = new $entityClass();

        $this->callPreFunctionByEventName(ProcessTrigger::EVENT_CREATE, $entity, $entityManager);

        $expectedTrigger = $triggers['create'];
        $expectedData = $this->createProcessData(array('data' => $entity));

        $this->logger->expects($this->once())->method('debug')
            ->with('Process handled', $expectedTrigger, $expectedData);
        $this->handler->expects($this->once())->method('handleTrigger')
            ->with($expectedTrigger, $expectedData);
        $entityManager->expects($this->once())->method('flush');

        $this->listener->postFlush(new PostFlushEventArgs($entityManager));
    }

    /**
     * @dataProvider postFlushQueuedProcessJobProvider
     * @param array $entityParams
     * @param array $expected
     */
    public function testPostFlushQueuedProcessJob($entityParams, $expected)
    {
        $callOrder     = 0;
        $entityClass   = self::ENTITY;
        $entity        = new $entityClass();
        $entityManager = $this->getEntityManager();
        $expectedData  = $this->createProcessData(array('data' => $entity));
        $triggers      = array();

        foreach ($entityParams as $entityParam) {
            $expectedTrigger = $this->getCustomQueuedTrigger($entityParam);
            $triggers[]      = $expectedTrigger;
        }

        $this->prepareRegistry($triggers);

        /** @var ProcessTrigger $trigger */
        foreach ($triggers as $iteration => $trigger) {
            $this->logger->expects($this->at($iteration))->method('debug')
                ->with('Process queued', $trigger, $expectedData);

            $this->callPreFunctionByEventName($trigger->getEvent(), new $entityClass(), $this->getEntityManager());

            $this->assertProcessJobPersist($entityManager, $entityParams, $iteration);
            $callOrder = $iteration;
        }

        $entityManager->expects($this->at(++$callOrder))->method('flush');

        foreach ($expected as $jmsParams) {
            $this->assertJMSJobPersist($entityManager, $jmsParams, ++$callOrder);
        }

        $entityManager->expects($this->at(++$callOrder))->method('flush');

        $this->listener->postFlush(new PostFlushEventArgs($entityManager));

        $this->assertAttributeEmpty('queuedJobs', $this->listener);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function postFlushQueuedProcessJobProvider()
    {
        return array(
            'all with same priority and timeShift (1 batch)' => array(
                'entities' => array(
                    ProcessTrigger::EVENT_CREATE => array(
                        'id'        => 1,
                        'event'     => ProcessTrigger::EVENT_CREATE,
                        'priority'  => 10,
                        'timeShift' => 60
                    ),
                    ProcessTrigger::EVENT_UPDATE => array(
                        'id'        => 2,
                        'event'     => ProcessTrigger::EVENT_UPDATE,
                        'priority'  => 10,
                        'timeShift' => 60
                    ),
                    ProcessTrigger::EVENT_DELETE => array(
                        'id'        => 3,
                        'event'     => ProcessTrigger::EVENT_DELETE,
                        'priority'  => 10,
                        'timeShift' => 60,
                    )
                ),
                'expected' => array(
                    array(
                        'commandArgs' => array('--id=1', '--id=2', '--id=3'),
                        'priority'    => 10,
                        'timeShift'   => 60
                    )
                )
            ),
            'one with different priority (2 batch)' => array(
                'entities' => array(
                    ProcessTrigger::EVENT_CREATE => array(
                        'id'        => 1,
                        'event'     => ProcessTrigger::EVENT_CREATE,
                        'priority'  => 90,
                        'timeShift' => 60
                    ),
                    ProcessTrigger::EVENT_UPDATE => array(
                        'id'        => 2,
                        'event'     => ProcessTrigger::EVENT_UPDATE,
                        'priority'  => 10,
                        'timeShift' => 60
                    ),
                    ProcessTrigger::EVENT_DELETE => array(
                        'id'        => 3,
                        'event'     => ProcessTrigger::EVENT_DELETE,
                        'priority'  => 10,
                        'timeShift' => 60
                    )
                ),
                'expected' => array(
                    array(
                        'commandArgs' => array('--id=1'),
                        'priority'    => 90,
                        'timeShift'   => 60
                    ),
                    array(
                        'commandArgs' => array('--id=2', '--id=3'),
                        'priority'    => 10,
                        'timeShift'   => 60
                    )
                )
            ),
            'all with same priority and different timeShift (3 batch)' => array(
                'entities' => array(
                    ProcessTrigger::EVENT_CREATE => array(
                        'id'        => 1,
                        'event'     => ProcessTrigger::EVENT_CREATE,
                        'priority'  => 10,
                        'timeShift' => 10
                    ),
                    ProcessTrigger::EVENT_UPDATE => array(
                        'id'        => 2,
                        'event'     => ProcessTrigger::EVENT_UPDATE,
                        'priority'  => 10,
                        'timeShift' => 20
                    ),
                    ProcessTrigger::EVENT_DELETE => array(
                        'id'        => 3,
                        'event'     => ProcessTrigger::EVENT_DELETE,
                        'priority'  => 10,
                        'timeShift' => 30
                    )
                ),
                'expected' => array(
                    array(
                        'commandArgs' => array('--id=1'),
                        'priority'    => 10,
                        'timeShift'   => 10
                    ),
                    array(
                        'commandArgs' => array('--id=2'),
                        'priority'    => 10,
                        'timeShift'   => 20
                    ),
                    array(
                        'commandArgs' => array('--id=3'),
                        'priority'    => 10,
                        'timeShift'   => 30
                    )
                )
            ),
            'all with same priority and only one with different timeShift (2 batch)' => array(
                'entities' => array(
                    ProcessTrigger::EVENT_CREATE => array(
                        'id'        => 1,
                        'event'     => ProcessTrigger::EVENT_CREATE,
                        'priority'  => 10,
                        'timeShift' => 10
                    ),
                    ProcessTrigger::EVENT_UPDATE => array(
                        'id'        => 2,
                        'event'     => ProcessTrigger::EVENT_UPDATE,
                        'priority'  => 10,
                        'timeShift' => 60
                    ),
                    ProcessTrigger::EVENT_DELETE => array(
                        'id'        => 3,
                        'event'     => ProcessTrigger::EVENT_DELETE,
                        'priority'  => 10,
                        'timeShift' => 60
                    )
                ),
                'expected' => array(
                    array(
                        'commandArgs' => array('--id=1'),
                        'priority'    => 10,
                        'timeShift'   => 10
                    ),
                    array(
                        'commandArgs' => array('--id=2', '--id=3'),
                        'priority'    => 10,
                        'timeShift'   => 60
                    )
                )
            )
        );
    }

    public function testPostFlushForceQueued()
    {
        $triggers = $this->getTriggers();
        $this->prepareRegistry($triggers);
        $entityManager = $this->getEntityManager();

        $this->listener->setForceQueued(true);

        $entityClass = self::ENTITY;
        $entity      = new $entityClass();

        // persist trigger is not queued
        $this->callPreFunctionByEventName(ProcessTrigger::EVENT_CREATE, $entity, $entityManager);

        // there is no need to check all trace - just ensure that job was queued
        $entityManager->expects($this->exactly(2))->method('persist');
        $entityManager->expects($this->exactly(2))->method('flush');

        $this->listener->postFlush(new PostFlushEventArgs($entityManager));
    }

    public function testPostFlushRemovedEntityHashes()
    {
        $this->prepareRegistry(array());
        $entityManager = $this->getEntityManager();

        $entityClass = self::ENTITY;
        $entity      = new $entityClass();
        $entityId    = 1;

        // expectations
        $expectedEntityHashes = array(ProcessJob::generateEntityHash($entityClass, $entityId));

        $this->doctrineHelper->expects($this->once())->method('getSingleEntityIdentifier')->with($entity, false)
            ->will($this->returnValue($entityId));

        $repository = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessJobRepository')
            ->disableOriginalConstructor()
            ->setMethods(array('deleteByHashes'))
            ->getMock();
        $repository->expects($this->once())->method('deleteByHashes');

        $this->registry->expects($this->at(1))->method('getRepository')->with('OroWorkflowBundle:ProcessJob')
            ->will($this->returnValue($repository));

        // test
        $this->callPreFunctionByEventName(ProcessTrigger::EVENT_DELETE, $entity, $entityManager);
        $this->assertAttributeEquals($expectedEntityHashes, 'removedEntityHashes', $this->listener);

        $this->listener->postFlush(new PostFlushEventArgs($entityManager));

        $this->assertAttributeEmpty('removedEntityHashes', $this->listener);
    }

    public function testSetForceQueued()
    {
        $this->assertAttributeEquals(false, 'forceQueued', $this->listener);
        $this->listener->setForceQueued(true);
        $this->assertAttributeEquals(true, 'forceQueued', $this->listener);
    }

    /**
     * @param ProcessTrigger[] $triggers
     */
    protected function prepareRegistry(array $triggers)
    {
        $repository = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessTriggerRepository')
            ->disableOriginalConstructor()
            ->setMethods(array('findAllWithDefinitions'))
            ->getMock();
        $repository->expects($this->any())->method('findAllWithDefinitions')->with(true)
            ->will($this->returnValue($triggers));

        $this->registry->expects($this->at(0))->method('getRepository')->with('OroWorkflowBundle:ProcessTrigger')
            ->will($this->returnValue($repository));
    }

    /**
     * @param int $triggerPriority
     * @return ProcessTrigger[]
     */
    protected function getTriggers($triggerPriority = 0)
    {
        $definition = new ProcessDefinition();
        $definition->setName('test')->setRelatedEntity(self::ENTITY);

        $createTrigger = new ProcessTrigger();
        $createTrigger->setDefinition($definition)
            ->setEvent(ProcessTrigger::EVENT_CREATE)
            ->setPriority($triggerPriority);

        $updateEntityTrigger = new ProcessTrigger();
        $updateEntityTrigger->setDefinition($definition)
            ->setEvent(ProcessTrigger::EVENT_UPDATE)
            ->setQueued(true)
            ->setPriority($triggerPriority)
            ->setTimeShift(60);

        $updateFieldTrigger = new ProcessTrigger();
        $updateFieldTrigger->setDefinition($definition)
            ->setEvent(ProcessTrigger::EVENT_UPDATE)
            ->setPriority($triggerPriority)
            ->setField(self::FIELD);

        $deleteTrigger = new ProcessTrigger();
        $deleteTrigger->setDefinition($definition)
            ->setPriority($triggerPriority)
            ->setEvent(ProcessTrigger::EVENT_DELETE);

        return array(
            'create' => $createTrigger,
            'updateEntity' => $updateEntityTrigger,
            'updateField' => $updateFieldTrigger,
            'delete' => $deleteTrigger,
        );
    }

    /**
     * @param array $config
     * @return ProcessTrigger
     */
    protected function getCustomQueuedTrigger(array $config)
    {
        $definition = new ProcessDefinition();
        $definition->setName('test-' . uniqid())->setRelatedEntity(self::ENTITY);

        $entityTrigger = new ProcessTrigger();
        $entityTrigger->setDefinition($definition)
            ->setEvent($config['event'])
            ->setQueued(true)
            ->setPriority($config['priority'])
            ->setTimeShift($config['timeShift']);

        return $entityTrigger;
    }

    /**
     * @param ProcessTrigger[] $triggers
     * @return array
     */
    protected function getExpectedTriggers(array $triggers)
    {
        $expectedTriggers = array();

        foreach ($triggers as $trigger) {
            $entityClass = $trigger->getDefinition()->getRelatedEntity();
            $event = $trigger->getEvent();
            $field = $trigger->getField();

            if ($event == ProcessTrigger::EVENT_UPDATE) {
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
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param string $event
     * @param object $entity
     * @param \PHPUnit_Framework_MockObject_MockObject $entityManager
     * @param array $changeSet
     */
    protected function callPreFunctionByEventName($event, $entity, $entityManager, $changeSet = array())
    {
        switch ($event) {
            case ProcessTrigger::EVENT_CREATE:
                $args = new LifecycleEventArgs($entity, $entityManager);
                $this->listener->prePersist($args);
                break;
            case ProcessTrigger::EVENT_UPDATE:
                $args = new PreUpdateEventArgs($entity, $entityManager, $changeSet);
                $this->listener->preUpdate($args);
                break;
            case ProcessTrigger::EVENT_DELETE:
                $args = new LifecycleEventArgs($entity, $entityManager);
                $this->listener->preRemove($args);
                break;
        }
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

                        $this->assertEquals($executeAfter, $jmsJob->getExecuteAfter());
                    }
                )
            );
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
}
