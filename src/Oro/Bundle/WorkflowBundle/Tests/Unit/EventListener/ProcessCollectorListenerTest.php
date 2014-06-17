<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;

use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\EventListener\ProcessCollectorListener;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;

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

        $this->listener = new ProcessCollectorListener($this->registry, $this->doctrineHelper, $this->handler);
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
                    'data' => new ProcessData(array('entity' => $entity))
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
                    'data' => new ProcessData(array('entity' => $entity))
                ),
                array(
                    'trigger' => $triggers['updateField'],
                    'data' => new ProcessData(array('entity' => $entity, 'old' => $oldValue, 'new' => $newValue))
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
                    'data' => new ProcessData(array('entity' => $entity))
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

        // assert internal data
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

        $this->registry->expects($this->any())->method('getRepository')->with('OroWorkflowBundle:ProcessTrigger')
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
            ->setEvent(ProcessTrigger::EVENT_UPDATE);

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
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
