<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\WorkflowBundle\Entity\EventTriggerInterface;
use Oro\Bundle\WorkflowBundle\EventListener\EventTriggerCollectorListener;
use Oro\Bundle\WorkflowBundle\EventListener\Extension\EventTriggerExtensionInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EventTriggerCollectorListenerTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY = 'stdClass';
    const FIELD  = 'field';

    /** @var EventTriggerCollectorListener */
    protected $listener;

    protected function setUp()
    {
        $this->listener = new EventTriggerCollectorListener();
    }

    protected function tearDown()
    {
        unset($this->listener);
    }

    public function testSetEnabled()
    {
        $this->assertAttributeEquals(true, 'enabled', $this->listener);

        $this->listener->setEnabled(false);

        $this->assertAttributeEquals(false, 'enabled', $this->listener);
    }

    public function testForceQueued()
    {
        $this->assertAttributeEquals(false, 'forceQueued', $this->listener);

        $extension = $this->getExtensionMock();
        $extension->expects($this->at(0))->method('setForceQueued')->with(false);
        $extension->expects($this->at(1))->method('setForceQueued')->with(true);

        $this->listener->addExtension($extension);
        $this->listener->setForceQueued(true);

        $this->assertAttributeEquals(true, 'forceQueued', $this->listener);
    }

    public function testAddExtension()
    {
        $this->assertAttributeEquals(new ArrayCollection(), 'extensions', $this->listener);

        $extension1 = $this->getExtensionMock(true);
        $extension2 = $this->getExtensionMock(true);

        $this->listener->setForceQueued(true);

        $this->listener->addExtension($extension1);
        $this->assertAttributeEquals(new ArrayCollection([$extension1]), 'extensions', $this->listener);

        $this->listener->addExtension($extension1);
        $this->assertAttributeEquals(new ArrayCollection([$extension1]), 'extensions', $this->listener);

        $this->listener->addExtension($extension2);
        $this->assertAttributeEquals(new ArrayCollection([$extension1, $extension2]), 'extensions', $this->listener);
    }

    /**
     * @dataProvider preFunctionNotEnabledProvider
     *
     * @param string $event
     */
    public function testPreFunctionNotEnabled($event)
    {
        $entity = new \stdClass();

        $extension = $this->getExtensionMock();
        $extension->expects($this->never())->method('hasTriggers');
        $extension->expects($this->never())->method('schedule');

        $this->listener->addExtension($extension);
        $this->listener->setEnabled(false);

        $this->callPreFunctionByEventName($event, $entity, $this->getEntityManagerMock());
    }

    /**
     * @return array
     */
    public function preFunctionNotEnabledProvider()
    {
        return [
            ['event' => EventTriggerInterface::EVENT_CREATE],
            ['event' => EventTriggerInterface::EVENT_UPDATE],
            ['event' => EventTriggerInterface::EVENT_DELETE]
        ];
    }

    /**
     * @dataProvider preFunctionProvider
     *
     * @param string $event
     * @param array|null $changeSet
     * @param array|null $expectedChangeSet
     */
    public function testPreFunction($event, array $changeSet = null, array $expectedChangeSet = null)
    {
        $entity = new \stdClass();

        $extension1 = $this->getExtensionMock();
        $extension1->expects($this->atLeastOnce())->method('hasTriggers')->with($entity, $event)->willReturn(true);
        $extension1->expects($this->atLeastOnce())->method('schedule')->with($entity, $event, $expectedChangeSet);

        $extension2 = $this->getExtensionMock();
        $extension2->expects($this->atLeastOnce())->method('hasTriggers')->with($entity, $event)->willReturn(false);
        $extension2->expects($this->never())->method('schedule');

        $this->listener->addExtension($extension1);
        $this->listener->addExtension($extension2);

        $this->callPreFunctionByEventName($event, $entity, $this->getEntityManagerMock(), $changeSet);
    }

    /**
     * @return array
     */
    public function preFunctionProvider()
    {
        $oldValue = 10;
        $newValue = 20;

        return [
            ['event' => EventTriggerInterface::EVENT_CREATE],
            [
                'event' => EventTriggerInterface::EVENT_UPDATE,
                'changeSet' => [self::FIELD => [$oldValue, $newValue]],
                'expectedChangeSet' => [self::FIELD => ['old' => $oldValue, 'new' => $newValue]]
            ],
            ['event' => EventTriggerInterface::EVENT_DELETE]
        ];
    }

    /**
     * @dataProvider onClearProvider
     *
     * @param OnClearEventArgs $args
     * @param string|null $entityClass
     */
    public function testOnClear(OnClearEventArgs $args, $entityClass)
    {
        $extension = $this->getExtensionMock();
        $extension->expects($this->atLeastOnce())->method('clear')->with($entityClass);

        $this->listener->addExtension($extension);
        $this->listener->onClear($args);
    }

    /**
     * @return array
     */
    public function onClearProvider()
    {
        return [
            'clear all' => [
                'args' => new OnClearEventArgs($this->getEntityManagerMock()),
                'entityClass' => null
            ],
            'clear entity class' => [
                'args' => new OnClearEventArgs($this->getEntityManagerMock(), self::ENTITY),
                'entityClass' => self::ENTITY
            ]
        ];
    }

    public function testPostFlush()
    {
        $em = $this->getEntityManagerMock();

        $extension = $this->getExtensionMock();
        $extension->expects($this->atLeastOnce())->method('process')->with($em);

        $this->listener->addExtension($extension);
        $this->listener->postFlush(new PostFlushEventArgs($em));
    }

    public function testPostFlushNotEnabled()
    {
        $extension = $this->getExtensionMock();
        $extension->expects($this->never())->method('process');

        $this->listener->addExtension($extension);
        $this->listener->setEnabled(false);
        $this->listener->postFlush(new PostFlushEventArgs($this->getEntityManagerMock()));
    }

    /**
     * @param string $event
     * @param object $entity
     * @param EntityManagerInterface $em
     * @param array $changeSet
     */
    protected function callPreFunctionByEventName($event, $entity, EntityManagerInterface $em, $changeSet = [])
    {
        switch ($event) {
            case EventTriggerInterface::EVENT_CREATE:
                $args = new LifecycleEventArgs($entity, $em);
                $this->listener->prePersist($args);
                break;
            case EventTriggerInterface::EVENT_UPDATE:
                $args = new PreUpdateEventArgs($entity, $em, $changeSet);
                $this->listener->preUpdate($args);
                break;
            case EventTriggerInterface::EVENT_DELETE:
                $args = new LifecycleEventArgs($entity, $em);
                $this->listener->preRemove($args);
                break;
        }
    }

    /**
     * @param null|bool $forceQueued
     * @return EventTriggerExtensionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getExtensionMock($forceQueued = null)
    {
        $mock = $this->getMock(EventTriggerExtensionInterface::class);

        if (null !== $forceQueued) {
            $mock->expects($this->once())->method('setForceQueued')->with($forceQueued);
        }

        return $mock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManagerInterface
     */
    protected function getEntityManagerMock()
    {
        return $this->getMock(EntityManagerInterface::class);
    }
}
