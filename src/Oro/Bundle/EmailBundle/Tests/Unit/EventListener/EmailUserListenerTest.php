<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\EmailBundle\EventListener\EmailUserListener;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\UserBundle\Entity\User;

class EmailUserListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var EmailUserListener */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $processor;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * @var UnitOfWork|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $uow;

    public function setUp()
    {
        $this->processor = $this->getMockBuilder('Oro\Bundle\EmailBundle\Model\WebSocket\WebSocketSendProcessor')
                ->disableOriginalConstructor()
                ->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($this->uow);

        $this->listener = new EmailUserListener($this->processor);
    }

    public function testFlush()
    {
        $changesetAnswer = ['seen' => true];

        $user1 = new User();
        $user1->setId(1);
        $user2 = new User();
        $user2->setId(2);
        $emailUser1 = new EmailUser();
        $emailUser1->setOwner($user1);
        $emailUser2 = new EmailUser();
        $emailUser2->setOwner($user2);

        $emailUserArray = [$emailUser1, $emailUser2, $emailUser1];

        $onFlushEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\OnFlushEventArgs')
            ->setMethods(['getEntityManager'])
            ->disableOriginalConstructor()
            ->getMock();
        $onFlushEventArgs
            ->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($this->em));
        $this->uow->expects($this->any())
            ->method('getEntityChangeSet')
            ->will($this->returnValue($changesetAnswer));
        $this->uow->expects($this->any())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue($emailUserArray));
        $this->uow->expects($this->any())
            ->method('getScheduledEntityUpdates')
            ->will($this->returnValue($emailUserArray));
        $this->processor
            ->expects($this->exactly(1))
            ->method('send')
            ->with(
                [
                    $user1->getId() => ['entity' => $emailUser1, 'new' => 2],
                    $user2->getId() => ['entity' => $emailUser2, 'new' => 1]
                ]
            );
        $postFlushEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\PostFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();

        $postFlushEventArgs->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->em);

        $this->listener->onFlush($onFlushEventArgs);
        $this->listener->postFlush($postFlushEventArgs);
    }
}
