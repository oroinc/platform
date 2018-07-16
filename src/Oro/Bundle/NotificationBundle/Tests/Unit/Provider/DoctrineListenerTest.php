<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Provider;

use Oro\Bundle\NotificationBundle\Provider\DoctrineListener;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

class DoctrineListenerTest extends TestCase
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

    protected function setUp()
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
     * @param $methodName
     * @param $eventName
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
            ->with($this->equalTo($eventName), $this->isInstanceOf('Symfony\Component\EventDispatcher\Event'));

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
