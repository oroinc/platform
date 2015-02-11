<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\Event\OnFlushEventArgs;

use Oro\Bundle\EmailBundle\Entity\Manager\EmailThreadManager;

class EmailThreadManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var EmailThreadManager */
    protected $manager;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $emailThreadProvider;

    protected function setUp()
    {
        $this->emailThreadProvider = $this->getMock('Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider');
        $this->manager = new EmailThreadManager($this->emailThreadProvider);
    }

    public function testHandleOnFlush()
    {
        $threadId = 'testThreadId';
        $emailFromThread = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $emailFromThread->expects($this->once())
            ->method('setThreadId')
            ->with($threadId);
        $this->emailThreadProvider->expects($this->once())
            ->method('getEmailThreadId')
            ->will($this->returnValue($threadId));
        $this->emailThreadProvider->expects($this->once())
            ->method('getEmailReferences')
            ->will($this->returnValue([$emailFromThread]));
        $email = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $email->expects($this->once())
            ->method('setThreadId')
            ->with($threadId);
        $email->expects($this->exactly(2))
            ->method('getThreadId')
            ->will($this->returnValue($threadId));
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $entityManager->expects($this->once())
            ->method('persist');

        $uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([$email, new \stdClass()]));

        $this->manager->handleOnFlush(new OnFlushEventArgs($entityManager));
    }
}
