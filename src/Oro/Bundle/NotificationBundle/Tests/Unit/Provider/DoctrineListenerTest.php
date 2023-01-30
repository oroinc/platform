<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\NotificationBundle\Doctrine\EntityPool;
use Oro\Bundle\NotificationBundle\Provider\DoctrineListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;

class DoctrineListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityPool|\PHPUnit\Framework\MockObject\MockObject */
    private $entityPool;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var DoctrineListener */
    private $listener;

    protected function setUp(): void
    {
        $this->entityPool = $this->createMock(EntityPool::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->listener = new DoctrineListener($this->entityPool, $this->eventDispatcher);
    }

    public function testPostFlush()
    {
        $args = $this->createMock(PostFlushEventArgs::class);

        $entityManager = $this->createMock(EntityManager::class);

        $args->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $this->entityPool->expects($this->once())
            ->method('persistAndFlush')
            ->with($entityManager);

        $this->listener->postFlush($args);
    }

    /**
     * @dataProvider eventDataProvider
     */
    public function testEventDispatchers(string $methodName, string $eventName)
    {
        $args = $this->createMock(LifecycleEventArgs::class);
        $args->expects($this->once())
            ->method('getEntity')
            ->willReturn('something');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(Event::class), $eventName);

        $this->listener->$methodName($args);
    }

    public function eventDataProvider(): array
    {
        return [
            'post update event case'  => [
                'method name'            => 'postUpdate',
                'expected event name'    => 'oro.notification.event.entity_post_update'
            ],
            'post persist event case' => [
                'method name'            => 'postPersist',
                'expected event name'    => 'oro.notification.event.entity_post_persist'
            ],
            'post remove event case'  => [
                'method name'            => 'postRemove',
                'expected event name'    => 'oro.notification.event.entity_post_remove'
            ],
        ];
    }
}
