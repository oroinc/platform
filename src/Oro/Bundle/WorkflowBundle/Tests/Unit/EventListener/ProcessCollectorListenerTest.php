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
        $args = new LifecycleEventArgs($entity, $this->getEntityManager());

        $this->listener->prePersist($args);

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
        $entity = new $entityClass();
        $oldValue = 1;
        $newValue = 2;
        $changeSet = array(self::FIELD => array($oldValue, $newValue));
        $args = new PreUpdateEventArgs($entity, $this->getEntityManager(), $changeSet);

        $this->listener->preUpdate($args);

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
        $entityId = 1;
        $entity = new $entityClass();
        $args = new LifecycleEventArgs($entity, $this->getEntityManager());

        $this->doctrineHelper->expects($this->once())->method('getSingleEntityIdentifier')->with($entity, false)
            ->will($this->returnValue($entityId));

        $this->listener->preRemove($args);

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
        $this->listener->prePersist(new LifecycleEventArgs(new $entityClass(), $this->getEntityManager()));

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

        $this->listener->prePersist(new LifecycleEventArgs($entity, $entityManager));

        $expectedTrigger = $triggers['create'];
        $expectedData = $this->createProcessData(array('data' => $entity));

        $this->logger->expects($this->once())->method('debug')
            ->with('Process handled', $expectedTrigger, $expectedData);
        $this->handler->expects($this->once())->method('handleTrigger')
            ->with($expectedTrigger, $expectedData);
        $entityManager->expects($this->once())->method('flush');

        $this->listener->postFlush(new PostFlushEventArgs($entityManager));
    }

    public function testPostFlushQueuedProcessJob()
    {
        $triggers = $this->getTriggers();
        $this->prepareRegistry($triggers);
        $entityManager = $this->getEntityManager();

        $entityClass = self::ENTITY;
        $entity = new $entityClass();
        $changeSet = array();
        $args = new PreUpdateEventArgs($entity, $entityManager, $changeSet);

        $this->listener->preUpdate($args);

        $expectedTrigger = $triggers['updateEntity'];
        $expectedData = $this->createProcessData(array('data' => $entity));
        $expectedJobId = 12;

        $entityManager->expects($this->at(0))->method('persist')
            ->with($this->isInstanceOf('Oro\Bundle\WorkflowBundle\Entity\ProcessJob'))
            ->will(
                $this->returnCallback(
                    function (ProcessJob $processJob) use ($expectedTrigger, $expectedData, $expectedJobId) {
                        $this->assertEquals($expectedTrigger, $processJob->getProcessTrigger());
                        $this->assertEquals($expectedData, $processJob->getData());

                        $idReflection = new \ReflectionProperty('Oro\Bundle\WorkflowBundle\Entity\ProcessJob', 'id');
                        $idReflection->setAccessible(true);
                        $idReflection->setValue($processJob, $expectedJobId);
                    }
                )
            );
        $entityManager->expects($this->at(1))->method('flush');
        $entityManager->expects($this->at(2))->method('persist')
            ->with($this->isInstanceOf('JMS\JobQueueBundle\Entity\Job'))
            ->will(
                $this->returnCallback(
                    function (Job $jmsJob) use ($expectedJobId) {
                        $this->assertEquals(ExecuteProcessJobCommand::NAME, $jmsJob->getCommand());
                        $this->assertEquals(array('--id=' . $expectedJobId), $jmsJob->getArgs());
                        $this->assertNotNull($jmsJob->getExecuteAfter());
                        $this->assertGreaterThan(new \DateTime(), $jmsJob->getExecuteAfter());
                    }
                )
            );
        $entityManager->expects($this->at(3))->method('flush');

        $this->logger->expects($this->once())->method('debug')
            ->with('Process queued', $expectedTrigger, $expectedData);

        $this->listener->postFlush(new PostFlushEventArgs($entityManager));

        $this->assertAttributeEmpty('queuedJobs', $this->listener);
    }

    public function testPostFlushForceQueued()
    {
        $triggers = $this->getTriggers();
        $this->prepareRegistry($triggers);
        $entityManager = $this->getEntityManager();

        $this->listener->setForceQueued(true);

        $entityClass = self::ENTITY;
        $entity = new $entityClass();
        $args = new LifecycleEventArgs($entity, $entityManager);

        // persist trigger is not queued
        $this->listener->prePersist($args);

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
        $entityId = 1;
        $entity = new $entityClass();

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
        $this->listener->preRemove(new LifecycleEventArgs($entity, $entityManager));

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
        $repository->expects($this->any())->method('findAllWithDefinitions')
            ->will($this->returnValue($triggers));

        $this->registry->expects($this->at(0))->method('getRepository')->with('OroWorkflowBundle:ProcessTrigger')
            ->will($this->returnValue($repository));
    }

    /**
     * @return ProcessTrigger[]
     */
    protected function getTriggers()
    {
        $definition = new ProcessDefinition();
        $definition->setName('test')->setRelatedEntity(self::ENTITY);

        $createTrigger = new ProcessTrigger();
        $createTrigger->setDefinition($definition)
            ->setEvent(ProcessTrigger::EVENT_CREATE);

        $updateEntityTrigger = new ProcessTrigger();
        $updateEntityTrigger->setDefinition($definition)
            ->setEvent(ProcessTrigger::EVENT_UPDATE)
            ->setQueued(true)
            ->setTimeShift(60);

        $updateFieldTrigger = new ProcessTrigger();
        $updateFieldTrigger->setDefinition($definition)
            ->setEvent(ProcessTrigger::EVENT_UPDATE)
            ->setField(self::FIELD);

        $deleteTrigger = new ProcessTrigger();
        $deleteTrigger->setDefinition($definition)
            ->setEvent(ProcessTrigger::EVENT_DELETE);

        return array(
            'create' => $createTrigger,
            'updateEntity' => $updateEntityTrigger,
            'updateField' => $updateFieldTrigger,
            'delete' => $deleteTrigger,
        );
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
