<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\EventListener;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\EventListener\UserPasswordListener;

class UserPasswordListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  UserPasswordListener */
    protected $listener;
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $uow;
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $eventArgs;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();

        $meta = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($this->uow);
        $em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf('Oro\Bundle\UserBundle\Entity\PasswordHash'));
        $em->expects($this->atLeastOnce())
            ->method('getClassMetadata')
            ->willReturn($meta);

        $this->eventArgs = $this->getMockBuilder('Doctrine\ORM\Event\OnFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventArgs->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);

        $this->listener = new UserPasswordListener();
    }

    /**
     * @param array $inserts
     * @param array $updates
     *
     * @dataProvider dataProvider
     */
    public function testPersistedUser($inserts, $updates)
    {
        $this->uow
            ->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn($inserts);
        $this->uow
            ->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn($updates);
        $this->uow
            ->expects($this->once())
            ->method('getEntityChangeSet')
            ->willReturn(['firstName' => ['Test']]);
            
        $this->listener->onFlush($this->eventArgs);
    }

    /**
     * @param array $inserts
     * @param array $updates
     *
     * @dataProvider dataProvider
     */
    public function testUpdatedUser($inserts, $updates)
    {
        $this->uow
            ->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $this->uow
            ->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn($updates);
        $this->uow
            ->expects($this->once())
            ->method('getEntityChangeSet')
            ->willReturn(['password' => ['Test']]);

        $this->listener->onFlush($this->eventArgs);
    }
    
    public function dataProvider()
    {
        $user = new User();
        $user->setSalt('test');
        $user->setPassword('9Er+$s');

        return [
            [
                'insertions' => [
                    $user, new \StdClass()
                ],
                'updates' => [
                    $user, new \StdClass()
                ]
            ]
        ];
    }
}
