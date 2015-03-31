<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

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
        $metaClass = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $thread = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailThread');
        $emailFromThread = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $emailFromThread->expects($this->once())
            ->method('setThread')
            ->with($thread);
        $this->emailThreadProvider->expects($this->once())
            ->method('getEmailThread')
            ->will($this->returnValue($thread));
        $this->emailThreadProvider->expects($this->once())
            ->method('getEmailReferences')
            ->will($this->returnValue([$emailFromThread]));
        $email = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $email->expects($this->once())
            ->method('setThread')
            ->with($thread);
        $email->expects($this->exactly(2))
            ->method('getThread')
            ->will($this->returnValue($thread));
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->exactly(3))
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $entityManager->expects($this->exactly(2))
            ->method('persist');
        $entityManager->expects($this->exactly(2))
            ->method('getClassMetadata')
            ->will($this->returnValue($metaClass));

        $uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([$email, new \stdClass()]));
        $uow->expects($this->exactly(2))
            ->method('computeChangeSet');

        $this->manager->handleOnFlush(new OnFlushEventArgs($entityManager));
    }

    public function testHandlePostFlush()
    {
        $thread = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailThread');
        $email = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $email->expects($this->exactly(1))
            ->method('getThread')
            ->will($this->returnValue($thread));
        $email->expects($this->exactly(1))
            ->method('getId')
            ->will($this->returnValue(1));

        $this->manager->addEmailToQueue($email);
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailThreadProvider->expects($this->once())
            ->method('getThreadEmails')
            ->will($this->returnValue([$email]));

        $this->manager->handlePostFlush(new PostFlushEventArgs($entityManager));
    }

    public function testHandlePostFlushWithEmptyQueue()
    {
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->exactly(0))
            ->method('persist');

        $this->manager->handlePostFlush(new PostFlushEventArgs($entityManager));
    }

    public function testAddResetQueue()
    {
        $emailFromThread1 = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $emailFromThread2 = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $this->manager->addEmailToQueue($emailFromThread1);
        $this->manager->addEmailToQueue($emailFromThread2);
        $this->assertCount(2, $this->manager->getQueue());
        $this->manager->resetQueue();
        $this->assertEmpty($this->manager->getQueue());
    }

    /**
     * @dataProvider dataProvider
     *
     * @param array $heads
     * @param array $seens
     * @param array $calls
     */
    public function testUpdateThreadHead(
        $heads,
        $seens,
        $calls
    ) {
        $threadId = 'testThreadId';
        $thread = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailThread');
        $thread->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));

        $emailFromThread1 = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $emailFromThread2 = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $emailFromThread3 = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');

        // reset
        $emailFromThread1->expects($this->at(0))
            ->method('setHead')
            ->with(false);
        $emailFromThread2->expects($this->at(0))
            ->method('setHead')
            ->with(false);
        $emailFromThread3->expects($this->at(0))
            ->method('setHead')
            ->with(false);

        // set heads
        if ($calls[0]) {
            $emailFromThread1->expects($this->at($calls[0]))
                ->method('setHead')
                ->with($heads[0]);
        }
        if ($calls[1]) {
            $emailFromThread2->expects($this->at($calls[1]))
                ->method('setHead')
                ->with($heads[1]);
        }
        if ($calls[2]) {
            $emailFromThread3->expects($this->at($calls[2]))
                ->method('setHead')
                ->with($heads[2]);
        }

        // mock seen
        $emailFromThread1->expects($this->exactly($calls[3]))
            ->method('isSeen')
            ->will($this->returnValue($seens[0]));
        $emailFromThread2->expects($this->exactly($calls[4]))
            ->method('isSeen')
            ->will($this->returnValue($seens[1]));
        $emailFromThread3->expects($this->exactly($calls[5]))
            ->method('isSeen')
            ->will($this->returnValue($seens[2]));

        $this->emailThreadProvider->expects($this->once())
            ->method('getThreadEmails')
            ->will($this->returnValue([$emailFromThread1, $emailFromThread2, $emailFromThread3]));

        $email = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $email->expects($this->exactly(1))
            ->method('getThread')
            ->will($this->returnValue($thread));
        $email->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->exactly(4))
            ->method('persist');

        $this->manager->addEmailToQueue($email);
        $this->manager->handlePostFlush(new PostFlushEventArgs($entityManager));

        $this->assertEmpty($this->manager->getQueue());
    }

    public function dataProvider()
    {
        return [
            'last unseen' =>
                [
                    'setHead' => [true, false, false],
                    'isSeen' => [false, false, false],
                    'calls' => [1, 0, 0, 0, 0, 0]
                ],
            'first all seen' =>
                [
                    'setHead' => [true, false, false],
                    'isSeen' => [true, true, true],
                    'calls' => [1, 0, 0, 0, 0, 0]
                ],
            'last unseen if the lastest is seen' =>
                [
                    'setHead' => [true, false, false],
                    'isSeen' => [true, false, false],
                    'calls' => [1, 0, 0, 0, 0, 0]
                ],
            'last unseen if and it is first' =>
                [
                    'setHead' => [true, false, false],
                    'isSeen' => [false, true, false],
                    'calls' => [1, 0, 0, 0, 0, 0]
                ],
            'unseen is single and last in list' =>
                [
                    'setHead' => [true, false, false],
                    'isSeen' => [true, true, false],
                    'calls' => [1, 0, 0, 0, 0, 0]
                ],
            'unseen is single and second in list' =>
                [
                    'setHead' => [true, false, false],
                    'isSeen' => [true, false, true],
                    'calls' => [1, 0, 0, 0, 0, 0]
                ],
        ];
    }
}
