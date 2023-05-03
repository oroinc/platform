<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\WorkflowBundle\Entity\EventTriggerInterface;
use Oro\Bundle\WorkflowBundle\EventListener\EventTriggerCollectorListener;
use Oro\Bundle\WorkflowBundle\EventListener\Extension\EventTriggerExtensionInterface;
use PHPUnit\Framework\MockObject\MockObject;

class EventTriggerCollectorListenerTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY = 'stdClass';
    private const FIELD = 'field';

    /** @var EventTriggerExtensionInterface|MockObject */
    private $extension1;

    /** @var EventTriggerExtensionInterface|MockObject */
    private $extension2;

    /** @var EventTriggerCollectorListener */
    private $listener;

    protected function setUp(): void
    {
        $this->extension1 = $this->createMock(EventTriggerExtensionInterface::class);
        $this->extension2 = $this->createMock(EventTriggerExtensionInterface::class);

        $this->listener = new EventTriggerCollectorListener([$this->extension1, $this->extension2]);
    }

    public function testSetEnabledFalsePreventsEventProcessingExceptOnClear()
    {
        $this->listener->setEnabled(false);

        $prePersistArgs = $this->createMock(LifecycleEventArgs::class);
        $prePersistArgs->expects(self::never())
            ->method('getEntity');

        $this->listener->prePersist($prePersistArgs);

        $preUpdateArgs = $this->createMock(PreUpdateEventArgs::class);
        $preUpdateArgs->expects(self::never())
            ->method('getEntityChangeSet');

        $this->listener->preUpdate($preUpdateArgs);

        $preRemoveArgs = $this->createMock(LifecycleEventArgs::class);
        $preRemoveArgs->expects(self::never())
            ->method('getEntity');

        $this->listener->preRemove($preRemoveArgs);

        $postFlushArgs = $this->createMock(PostFlushEventArgs::class);
        $postFlushArgs->expects(self::never())
            ->method('getEntityManager');

        $this->listener->postFlush($postFlushArgs);

        $onClearArgs = $this->createMock(OnClearEventArgs::class);
        $onClearArgs->expects(self::once())
            ->method('getEntityClass')
            ->willReturn('EntityClass');
        $this->extension1->expects(self::once())
            ->method('clear')
            ->with('EntityClass');
        $this->extension2->expects(self::once())
            ->method('clear')
            ->with('EntityClass');

        $this->listener->onClear($onClearArgs);
    }

    public function testForceQueued()
    {
        $this->extension1->expects(self::once())
            ->method('setForceQueued')
            ->with(true);
        $this->extension2->expects(self::once())
            ->method('setForceQueued')
            ->with(true);

        $this->listener->setForceQueued(true);
        // force initializing of extensions
        $this->listener->preRemove(
            new LifecycleEventArgs(new \stdClass(), $this->createMock(EntityManagerInterface::class))
        );
    }

    /**
     * @dataProvider preFunctionNotEnabledProvider
     */
    public function testPreFunctionNotEnabled(string $event)
    {
        $entity = new \stdClass();

        $this->extension1->expects(self::never())
            ->method('hasTriggers');
        $this->extension1->expects(self::never())
            ->method('schedule');
        $this->extension2->expects(self::never())
            ->method('hasTriggers');
        $this->extension2->expects(self::never())
            ->method('schedule');

        $this->listener->setEnabled(false);

        $this->callPreFunctionByEventName($event, $entity, $this->createMock(EntityManagerInterface::class));
    }

    public function preFunctionNotEnabledProvider(): array
    {
        return [
            ['event' => EventTriggerInterface::EVENT_CREATE],
            ['event' => EventTriggerInterface::EVENT_UPDATE],
            ['event' => EventTriggerInterface::EVENT_DELETE]
        ];
    }

    /**
     * @dataProvider preFunctionProvider
     */
    public function testPreFunction(string $event, array $changeSet = null, array $expectedChangeSet = null)
    {
        $entity = new \stdClass();

        $this->extension1->expects(self::atLeastOnce())
            ->method('hasTriggers')
            ->with($entity, $event)
            ->willReturn(true);
        $this->extension1->expects(self::atLeastOnce())
            ->method('schedule')
            ->with($entity, $event, $expectedChangeSet);
        $this->extension2->expects(self::atLeastOnce())
            ->method('hasTriggers')
            ->with($entity, $event)
            ->willReturn(true);
        $this->extension2->expects(self::atLeastOnce())
            ->method('schedule')
            ->with($entity, $event, $expectedChangeSet);

        $this->callPreFunctionByEventName(
            $event,
            $entity,
            $this->createMock(EntityManagerInterface::class),
            $changeSet
        );
    }

    public function preFunctionProvider(): array
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
     */
    public function testOnClear(OnClearEventArgs $args, ?string $entityClass)
    {
        $this->extension1->expects(self::atLeastOnce())
            ->method('clear')
            ->with($entityClass);
        $this->extension2->expects(self::atLeastOnce())
            ->method('clear')
            ->with($entityClass);

        $this->listener->onClear($args);
    }

    public function onClearProvider(): array
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

        $this->extension1->expects(self::atLeastOnce())
            ->method('process')
            ->with($em);
        $this->extension2->expects(self::atLeastOnce())
            ->method('process')
            ->with($em);

        $this->listener->postFlush(new PostFlushEventArgs($em));
    }

    public function testPostFlushNotEnabled()
    {
        $this->extension1->expects(self::never())
            ->method('process');
        $this->extension2->expects(self::never())
            ->method('process');

        $this->listener->setEnabled(false);
        $this->listener->postFlush(new PostFlushEventArgs($this->createMock(EntityManagerInterface::class)));
    }

    private function callPreFunctionByEventName(
        string $event,
        object $entity,
        EntityManagerInterface $em,
        ?array $changeSet = []
    ): void {
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
