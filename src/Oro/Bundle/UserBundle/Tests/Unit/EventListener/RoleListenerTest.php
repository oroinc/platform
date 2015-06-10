<?php

namespace Oro\Bundle\UserBundle\Tests\EventListener;

use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\EventListener\RoleListener;

class RoleListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $serviceLink;

    /**
     * @var RoleListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->serviceLink = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new RoleListener($this->serviceLink);
    }

    public function testPrePersistNotSupportedEntity()
    {
        $entity = new \stdClass();
        $event = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($entity));
        $event->expects($this->never())
            ->method('getEntityManager');

        $this->listener->prePersist($event);
    }

    /**
     * Test prePersist role that to generate new value of "role" field
     */
    public function testPrePersistValid()
    {
        $role = new Role();

        $this->assertEmpty($role->getId());
        $this->assertEmpty($role->getRole());

        $this->listener->prePersist($this->getPrePersistEvent($role));

        $this->assertNotEmpty($role->getRole());
    }

    /**
     * Test prePersist role that generate exception \LogicException
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage 10 attempts to generate unique role are failed.
     */
    public function testPrePersistInValid()
    {
        $entity = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Role')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener->prePersist($this->getPrePersistEvent($entity, true));
    }

    /**
     * @param object $entity
     * @param bool $duplicate
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPrePersistEvent($entity, $duplicate = false)
    {
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $repository = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();

        if ($duplicate) {
            $repository->expects($this->any())
                ->method('findOneBy')
                ->will($this->returnValue($entity));
        }

        $em->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $event = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($em));
        $event->expects($this->any())
            ->method('getEntity')
            ->will($this->returnValue($entity));

        return $event;
    }
}
