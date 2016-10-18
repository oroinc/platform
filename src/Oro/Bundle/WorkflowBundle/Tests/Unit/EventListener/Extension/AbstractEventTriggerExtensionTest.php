<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener\Extension;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\WorkflowBundle\Cache\EventTriggerCache;
use Oro\Bundle\WorkflowBundle\Entity\EventTriggerInterface;
use Oro\Bundle\WorkflowBundle\EventListener\Extension\AbstractEventTriggerExtension;

use Oro\Component\Testing\Unit\EntityTrait;

abstract class AbstractEventTriggerExtensionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const ENTITY_CLASS = WorkflowAwareEntity::class;
    const FIELD = 'name';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManager */
    protected $entityManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EventTriggerCache */
    protected $triggerCache;

    /** @var AbstractEventTriggerExtension */
    protected $extension;

    /** @var array */
    protected $triggers;

    protected function setUp()
    {
        $this->entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)->disableOriginalConstructor()->getMock();

        $this->triggerCache = $this->getMockBuilder(EventTriggerCache::class)->disableOriginalConstructor()->getMock();
    }

    protected function tearDown()
    {
        unset($this->extension, $this->doctrineHelper, $this->triggerCache, $this->entityManager, $this->triggers);
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
     * @param array $triggers
     */
    protected function prepareRepository(array $triggers = null)
    {
        $this->repository->expects($this->once())
            ->method('findAllWithDefinitions')
            ->with(true)
            ->willReturn($triggers ?: $this->getTriggers());
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
     * @param null|string $triggerName
     * @return EventTriggerInterface[]|EventTriggerInterface
     */
    abstract protected function getTriggers($triggerName = null);

    /**
     * @param EventTriggerInterface[] $triggers
     * @return array
     */
    protected function getExpectedTriggers(array $triggers)
    {
        $expectedTriggers = [];

        foreach ($triggers as $trigger) {
            $entityClass = $trigger->getEntityClass();
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
     * @param int $id
     * @return WorkflowAwareEntity|object
     */
    protected function getMainEntity($id = 42)
    {
        return $this->getEntity(self::ENTITY_CLASS, ['id' => $id]);
    }
}
