<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Provider;

use Oro\Bundle\NotificationBundle\Provider\DoctrineListener;
use Symfony\Contracts\EventDispatcher\Event;

class DoctrineListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DoctrineListener
     */
    protected $listener;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityPool;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $eventDispatcher;

    protected function setUp(): void
    {
        $this->entityPool = $this->createMock('Oro\Bundle\NotificationBundle\Doctrine\EntityPool');
        $this->eventDispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->listener = new DoctrineListener($this->entityPool, $this->eventDispatcher);
    }

    public function testPostFlush()
    {
        $args = $this->getMockBuilder('Doctrine\ORM\Event\PostFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $args->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($entityManager));

        $this->entityPool->expects($this->once())
            ->method('persistAndFlush')
            ->with($entityManager);

        $this->listener->postFlush($args);
    }

    /**
     * @dataProvider eventData
     */
    public function testEventDispatchers($methodName, $eventName)
    {
        $args = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $args->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue('something'));

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(Event::class), $this->equalTo($eventName));

        $this->listener->$methodName($args);
    }

    /**
     * data provider
     */
    public function eventData()
    {
        return array(
            'post update event case'  => array(
                'method name'            => 'postUpdate',
                'expected event name'    => 'oro.notification.event.entity_post_update'
            ),
            'post persist event case' => array(
                'method name'            => 'postPersist',
                'expected event name'    => 'oro.notification.event.entity_post_persist'
            ),
            'post remove event case'  => array(
                'method name'            => 'postRemove',
                'expected event name'    => 'oro.notification.event.entity_post_remove'
            ),
        );
    }
}
