<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\Event\OnFlushEventArgs;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\EmailAddress;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser;

class EmailActivityManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $activityManager;

    /** @var EmailActivityManager */
    private $manager;

    private $owners;

    protected function setUp()
    {
        $this->activityManager = $this->getMockBuilder('Oro\Bundle\ActivityBundle\Manager\ActivityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new EmailActivityManager($this->activityManager);

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
        $email      = $this->getEmailEntity();
        $emailClass = get_class($email);

        $em            = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $uow           = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $classMetaData = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([$email, new \stdClass()]));
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->with($emailClass)
            ->will($this->returnValue($classMetaData));
        $uow->expects($this->once())
            ->method('computeChangeSet')
            ->with($this->identicalTo($classMetaData), $this->identicalTo($email));

        $this->activityManager->expects($this->at(0))
            ->method('addActivityTargets')
            ->with($this->identicalTo($email), $this->identicalTo($this->owners))
            ->will($this->returnValue(true));

        $this->manager->handleOnFlush(new OnFlushEventArgs($em));
    }

    public function testHandleOnFlushWithoutNewEmails()
    {
        $em  = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue([new \stdClass()]));
        $uow->expects($this->never())
            ->method('computeChangeSet');

        $this->manager->handleOnFlush(new OnFlushEventArgs($em));
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
