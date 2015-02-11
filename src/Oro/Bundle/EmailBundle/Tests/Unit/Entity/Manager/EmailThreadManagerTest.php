<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\Event\OnFlushEventArgs;

use Oro\Bundle\EmailBundle\Entity\Manager\EmailThreadManager;

class EmailThreadManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var EmailThreadManager */
    private $manager;

    protected function setUp()
    {
        $this->manager = new EmailThreadManager();
    }

    public function testHandleOnFlushWithExistingThread()
    {
        $email = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $email->expects($this->once())
            ->method('getThreadId')
            ->will($this->returnValue(uniqid()));
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([$email, new \stdClass()]));

        $this->manager->handleOnFlush(new OnFlushEventArgs($entityManager));
    }

    public function testHandleOnFlushWithExistingXThread()
    {
        $email = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $xThreadId = uniqid();
        $email->expects($this->exactly(2))
            ->method('getXThreadId')
            ->will($this->returnValue($xThreadId));
        $email->expects($this->once())
            ->method('setThreadId')
            ->with($xThreadId);
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([$email, new \stdClass()]));

        $this->manager->handleOnFlush(new OnFlushEventArgs($entityManager));
    }

    public function testHandleOnFlushWithExistingRefs()
    {
        $email = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $email->expects($this->once())
            ->method('getThreadId');
        $email->expects($this->once())
            ->method('getXThreadId');
        $email->expects($this->exactly(2))
            ->method('getRefs')
            ->will($this->returnValue('testMessageId'));
        $threadId = 'testThreadId';
        $email->expects($this->once())
            ->method('setThreadId')
            ->with($threadId);
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([$email, new \stdClass()]));
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getSingleResult'])
            ->getMockForAbstractClass();
        $newEmail = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $newEmail->expects($this->once())
            ->method('getThreadId')
            ->will($this->returnValue($threadId));
        $query->expects($this->once())
            ->method('getSingleResult')
            ->will($this->returnValue($newEmail));
        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilder);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with('OroEmailBundle:Email')
            ->will($this->returnValue($repository));

        $this->manager->handleOnFlush(new OnFlushEventArgs($entityManager));
    }

    public function testHandleOnFlushWithNewThreadId()
    {
        $email = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $email->expects($this->once())
            ->method('getThreadId');
        $email->expects($this->once())
            ->method('getXThreadId');
        $email->expects($this->once())
            ->method('getRefs');
        $email->expects($this->once())
            ->method('setThreadId');

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([$email, new \stdClass()]));

        $this->manager->handleOnFlush(new OnFlushEventArgs($entityManager));
    }
}
