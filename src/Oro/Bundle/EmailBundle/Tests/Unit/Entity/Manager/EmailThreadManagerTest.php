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
            ->method('setHead')
            ->with(false);
        $emailFromThread->expects($this->once())
            ->method('setThreadId')
            ->with($threadId);
        $this->emailThreadProvider->expects($this->once())
            ->method('getEmailThreadId')
            ->will($this->returnValue($threadId));
        $this->emailThreadProvider->expects($this->once())
            ->method('getEmailReferences')
            ->will($this->returnValue([$emailFromThread]));
        $this->emailThreadProvider->expects($this->once())
            ->method('getThreadEmails')
            ->will($this->returnValue([$emailFromThread]));
        $email = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $email->expects($this->once())
            ->method('setThreadId')
            ->with($threadId);
        $email->expects($this->exactly(3))
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
        $entityManager->expects($this->exactly(3))
            ->method('persist');

        $uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([$email, new \stdClass()]));

        $this->manager->handleOnFlush(new OnFlushEventArgs($entityManager));
    }

    /**
     * @dataProvider dataProvider
     *
     * @param $head
     * @param $firstHead
     * @param $secondHead
     * @param $seen
     * @param $firstSeen
     * @param $secondSeen
     * @param $calls
     */
    public function testUpdateThreadHead($head, $firstHead, $secondHead, $seen, $firstSeen, $secondSeen, $calls)
    {
        $threadId = 'testThreadId';
        $emailFromThread1 = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $emailFromThread2 = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $emailFromThread1->expects($this->at($calls[0]))
            ->method('setHead')
            ->with($firstHead);
        $emailFromThread2->expects($this->at($calls[1]))
            ->method('setHead')
            ->with($secondHead);
        if ($calls[6]) {
            $emailFromThread2->expects($this->at(2))
                ->method('setHead')
                ->with(true);
        }
        $emailFromThread1->expects($this->exactly($calls[2]))
            ->method('isSeen')
            ->will($this->returnValue($firstSeen));
        $emailFromThread2->expects($this->exactly($calls[3]))
            ->method('isSeen')
            ->will($this->returnValue($secondSeen));
        $this->emailThreadProvider->expects($this->once())
            ->method('getThreadEmails')
            ->will($this->returnValue([$emailFromThread1, $emailFromThread2]));

        $email = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $email->expects($this->exactly($calls[4]))
            ->method('getThreadId')
            ->will($this->returnValue($threadId));
        $email->expects($this->exactly($calls[5]))
            ->method('setHead')
            ->with($head);
        $email->expects($this->once())
            ->method('isSeen')
            ->will($this->returnValue($seen));

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this->exactly(3))
            ->method('persist');

        $this->manager->updateThreadHead($entityManager, $email);
    }

    public function dataProvider()
    {
        return [
            'last unseen' =>
                [true, false, false, false, false, false, [0, 0, 0, 0, 1, 1, 0]],
            'first all seen' =>
                [true, false, false, true, true, true, [0, 0, 1, 1, 1, 0, 0]],
            'last unseen if the lastest is seen' =>
                [false, false, false, true, true, false, [0, 0, 1, 1, 1, 0, 1]],
        ];
    }
}
