<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\WorkflowBundle\Entity\EventTriggerInterface;
use Oro\Bundle\WorkflowBundle\EventListener\EventTriggerCollectorListener;
use Oro\Bundle\WorkflowBundle\EventListener\Extension\EventTriggerExtensionInterface;
use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;

class EventTriggerCollectorListenerTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY = 'stdClass';
    private const FIELD  = 'field';

    /** @var EventTriggerExtensionInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $extension;

    /** @var EventTriggerCollectorListener */
    private $listener;

    protected function setUp()
    {
        $this->extension = $this->createMock(EventTriggerExtensionInterface::class);

        $this->listener = new EventTriggerCollectorListener(new RewindableGenerator(
            function () {
                yield $this->extension;
            },
            1
        ));
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

        $this->extension->expects($this->once())
            ->method('setForceQueued')
            ->with(true);

        $this->listener->setForceQueued(true);
        // force initializing of extensions
        $this->listener->preRemove(
            new LifecycleEventArgs(new \stdClass(), $this->createMock(EntityManagerInterface::class))
        );

        $this->assertAttributeEquals(true, 'forceQueued', $this->listener);
    }

    /**
     * @dataProvider preFunctionNotEnabledProvider
     *
     * @param string $event
     */
    public function testPreFunctionNotEnabled($event)
    {
        $entity = new \stdClass();

        $this->extension->expects($this->never())
            ->method('hasTriggers');
        $this->extension->expects($this->never())
            ->method('schedule');

        $this->listener->setEnabled(false);

        $this->callPreFunctionByEventName($event, $entity, $this->createMock(EntityManagerInterface::class));
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

        $this->extension->expects($this->atLeastOnce())
            ->method('hasTriggers')
            ->with($entity, $event)
            ->willReturn(true);
        $this->extension->expects($this->atLeastOnce())
            ->method('schedule')
            ->with($entity, $event, $expectedChangeSet);

        $this->callPreFunctionByEventName(
            $event,
            $entity,
            $this->createMock(EntityManagerInterface::class),
            $changeSet
        );
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
        $this->extension->expects($this->atLeastOnce())
            ->method('clear')
            ->with($entityClass);

        $this->listener->onClear($args);
    }

    /**
     * @return array
     */
    public function onClearProvider()
    {
        return [
            'clear all' => [
                'args' => new OnClearEventArgs($this->createMock(EntityManagerInterface::class)),
                'entityClass' => null
            ],
            'clear entity class' => [
                'args' => new OnClearEventArgs($this->createMock(EntityManagerInterface::class), self::ENTITY),
                'entityClass' => self::ENTITY
            ]
        ];
    }

    public function testPostFlush()
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $this->extension->expects($this->atLeastOnce())
            ->method('process')
            ->with($em);

        $this->listener->postFlush(new PostFlushEventArgs($em));
    }

    public function testPostFlushNotEnabled()
    {
        $this->extension->expects($this->never())
            ->method('process');

        $this->listener->setEnabled(false);
        $this->listener->postFlush(new PostFlushEventArgs($this->createMock(EntityManagerInterface::class)));
    }

    /**
     * @param string $event
     * @param object $entity
     * @param EntityManagerInterface $em
     * @param array $changeSet
     */
    private function callPreFunctionByEventName($event, $entity, EntityManagerInterface $em, $changeSet = [])
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
}
