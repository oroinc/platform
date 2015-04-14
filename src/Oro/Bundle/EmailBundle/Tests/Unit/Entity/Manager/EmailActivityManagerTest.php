<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\Event\OnFlushEventArgs;

use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
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
            ->getMock();
        $this->emailThreadProvider = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider')
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
            new TestUser('4'),
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

    public function testHandlePostFlush()
    {
        $thread = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailThread');
        $email = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email');
        $email->expects($this->exactly(2))
            ->method('getThread')
            ->will($this->returnValue($thread));
        $email->expects($this->exactly(0))
            ->method('getId')
            ->will($this->returnValue(1));
        $email->expects($this->exactly(1))
            ->method('getRecipients')
            ->will($this->returnValue([]));

        $this->manager->addEmailToQueue($email);
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->exactly(2))
            ->method('getRepository')
            ->will($this->returnValue($repository));
        $repository->expects($this->never())
            ->method('findByThread')
            ->will($this->returnValue([$email]));

        $this->emailThreadProvider->expects($this->never())
            ->method('getThreadEmails')
            ->will($this->returnValue([$email]));

        $this->emailActivityListProvider->expects($this->once())
            ->method('getTargetEntities')
            ->will($this->returnValue([]));

        $this->manager->handlePostFlush(new PostFlushEventArgs($entityManager));
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
        $email = $this->getMock('Oro\Bundle\EmailBundle\Entity\Email', ['addActivityTarget']);

        $this->addEmailSender($email, $this->owners[0]);

        $this->addEmailRecipient($email, $this->owners[1]);
        $this->addEmailRecipient($email, $this->owners[2]);
        $this->addEmailRecipient($email, $this->owners[3]);
        $this->addEmailRecipient($email, $this->owners[0]);
        $this->addEmailRecipient($email, $this->owners[1]);
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
