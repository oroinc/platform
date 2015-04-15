<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\EntityManager;

use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\EmailAddress;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser;

class EmailActivityManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $activityManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $emailActivityListProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $emailThreadProvider;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /** @var EmailActivityManager */
    private $manager;

    private $owners;

    protected function setUp()
    {
        $this->activityManager = $this->getMockBuilder('Oro\Bundle\ActivityBundle\Manager\ActivityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailActivityListProvider = $this
            ->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider')
            ->disableOriginalConstructor()
            ->setMethods(['getTargetEntities'])
            ->getMock();
        $this->emailThreadProvider = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new EmailActivityManager(
            $this->activityManager,
            $this->emailActivityListProvider,
            $this->emailThreadProvider
        );

        $this->owners = [
            new TestUser('1'),
            new TestUser('2'),
            new TestUser('3'),
            new TestUser('4')
        ];
    }

    public function testAddAssociation()
    {
        $email  = $this->getEmailEntity();
        $target = new TestUser();

        $this->activityManager->expects($this->once())
            ->method('addActivityTarget')
            ->with($this->identicalTo($email), $this->identicalTo($target))
            ->will($this->returnValue(true));

        $this->assertTrue(
            $this->manager->addAssociation($email, $target)
        );
    }

    public function testHandleOnFlush()
    {
        $email = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->exactly(1))
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));

        $uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([$email, new \stdClass()]));

        $this->manager->handleOnFlush(new OnFlushEventArgs($entityManager));

        $this->assertCount(1, $this->manager->getQueue());
    }

    public function testHandlePostFlushEmptyThread()
    {
        $email = $this->getEmailEntity();
        $this->manager->addEmailToQueue($email);

        $this->emailThreadProvider->expects($this->never())
            ->method('getThreadEmails')
            ->will($this->returnValue([$email]));

        $this->emailActivityListProvider
            ->method('getTargetEntities')
            ->will($this->returnValue([]));

        $this->manager->handlePostFlush(new PostFlushEventArgs($this->entityManager));
        $this->assertCount(0, $this->manager->getQueue());
    }

    public function testHandlePostFlush()
    {
        $email = $this->getEmailEntity();
        $email->setThread(1);
        $email->expects($this->exactly(1))
              ->method('getId')
              ->will($this->returnValue(1));

        $email2 = $this->getEmailEntity();
        $email2->expects($this->exactly(1))
            ->method('getId')
               ->will($this->returnValue(2));

        $this->handlePostFlushPrepareData($email, $email2);

        $this->emailActivityListProvider->expects($this->exactly(2))
            ->method('getTargetEntities')
            ->will($this->returnValue([$this->owners[2]]));


        $this->manager->handlePostFlush(new PostFlushEventArgs($this->entityManager));
        $this->assertCount(0, $this->manager->getQueue());
    }

    public function testHandlePostFlushWithOutContexts()
    {
        $email = $this->getEmailEntity();
        $email->setThread(1);
        $email->method('getId')
            ->will($this->returnValue(1));

        $email2 = $this->getEmailEntity();
        $email2->method('getId')
            ->will($this->returnValue(2));

        $this->handlePostFlushPrepareData($email, $email2);

        $this->emailActivityListProvider->expects($this->exactly(3))
            ->method('getTargetEntities')
            ->will($this->returnValue([]));

        $this->emailThreadProvider->expects($this->exactly(1))
            ->method('getEmailReferences')
            ->will($this->returnValue([$email2]));

        $this->manager->handlePostFlush(new PostFlushEventArgs($this->entityManager));
    }

    protected function handlePostFlushPrepareData($email, $email2)
    {
        $this->manager->addEmailToQueue($email);

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->setMethods(['findByThread'])
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->exactly(2))
            ->method('findByThread')
            ->withAnyParameters()
            ->will($this->returnValue([$email2]));

        $this->entityManager->expects($this->exactly(2))
            ->method('getRepository')
            ->with(Email::ENTITY_CLASS)
            ->will($this->returnValue($repository));

        $this->emailThreadProvider->expects($this->never())
            ->method('getThreadEmails')
            ->will($this->returnValue([$email2]));
    }

    public function testHandleOnFlushWithoutNewEmails()
    {
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->exactly(1))
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));

        $uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([new \stdClass()]));

        $this->manager->handleOnFlush(new OnFlushEventArgs($entityManager));

        $this->assertCount(0, $this->manager->getQueue());
    }

    /**
     * @return Email|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEmailEntity()
    {
        /** @var Email $email */
        $email = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email', ['addActivityTarget','getId']);

        $this->addEmailSender($email, $this->owners[0]);

        $this->addEmailRecipient($email, $this->owners[1]);
        $this->addEmailRecipient($email, $this->owners[2]);
        $this->addEmailRecipient($email, $this->owners[3]);
        $this->addEmailRecipient($email, $this->owners[0]);
        $this->addEmailRecipient($email, null);

        return $email;
    }

    /**
     * @param Email       $email
     * @param object|null $owner
     */
    protected function addEmailSender(Email $email, $owner = null)
    {
        $emailAddr = new EmailAddress();
        $emailAddr->setOwner($owner);

        $email->setFromEmailAddress($emailAddr);
    }

    /**
     * @param Email       $email
     * @param object|null $owner
     */
    protected function addEmailRecipient(Email $email, $owner = null)
    {
        $emailAddr = new EmailAddress();
        $emailAddr->setOwner($owner);

        $recipient = new EmailRecipient();
        $recipient->setEmailAddress($emailAddr);

        $email->addRecipient($recipient);
    }
}
