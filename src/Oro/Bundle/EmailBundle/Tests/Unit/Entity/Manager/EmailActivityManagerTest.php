<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\Event\OnFlushEventArgs;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\EmailAddress;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;

class EmailActivityManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $activityConfigProvider;

    /** @var EmailActivityManager */
    private $manager;

    private $owners;

    protected function setUp()
    {
        $this->activityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new EmailActivityManager($this->activityConfigProvider);

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

        $targetClass = get_class($target);
        $emailClass  = get_class($email);

        $config = new Config(new EntityConfigId('activity', $targetClass));
        $config->set('activities', [$emailClass]);

        $this->activityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($targetClass)
            ->will($this->returnValue(true));
        $this->activityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($targetClass)
            ->will($this->returnValue($config));

        $email->expects($this->once())
            ->method('addActivityTarget')
            ->with($this->identicalTo($target));

        $result = $this->manager->addAssociation($email, $target);

        $this->assertTrue($result);
    }

    public function testAddAssociationForNotConfigurableTarget()
    {
        $email  = $this->getEmailEntity();
        $target = new TestUser();

        $targetClass = get_class($target);
        $emailClass  = get_class($email);

        $config = new Config(new EntityConfigId('activity', $targetClass));
        $config->set('activities', [$emailClass]);

        $this->activityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($targetClass)
            ->will($this->returnValue(false));
        $this->activityConfigProvider->expects($this->never())
            ->method('getConfig');

        $email->expects($this->never())
            ->method('addActivityTarget');

        $result = $this->manager->addAssociation($email, $target);

        $this->assertFalse($result);
    }

    public function testAddAssociationForTargetWithoutEmailAssociation()
    {
        $email  = $this->getEmailEntity();
        $target = new TestUser();

        $targetClass = get_class($target);

        $config = new Config(new EntityConfigId('activity', $targetClass));
        $config->set('activities', ['Test\Entity']);

        $this->activityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($targetClass)
            ->will($this->returnValue(true));
        $this->activityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($targetClass)
            ->will($this->returnValue($config));

        $email->expects($this->never())
            ->method('addActivityTarget');

        $result = $this->manager->addAssociation($email, $target);

        $this->assertFalse($result);
    }

    public function testAddAssociationForTargetWithoutAnyAssociations()
    {
        $email  = $this->getEmailEntity();
        $target = new TestUser();

        $targetClass = get_class($target);

        $config = new Config(new EntityConfigId('activity', $targetClass));

        $this->activityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($targetClass)
            ->will($this->returnValue(true));
        $this->activityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($targetClass)
            ->will($this->returnValue($config));

        $email->expects($this->never())
            ->method('addActivityTarget');

        $result = $this->manager->addAssociation($email, $target);

        $this->assertFalse($result);
    }

    public function testHandleOnFlush()
    {
        $email      = $this->getEmailEntity();
        $emailClass = get_class($email);

        /** @var Config[] $configs */
        $configs = [];
        foreach ($this->owners as $owner) {
            $configs[] = new Config(new EntityConfigId('activity', get_class($owner)));
        }
        $configs[0]->set('activities', [$emailClass]);
        $configs[1]->set('activities', ['Test\Entity']);
        $configs[3]->set('activities', [$emailClass]);

        $this->activityConfigProvider->expects($this->exactly(4))
            ->method('hasConfig')
            ->with($configs[0]->getId()->getClassName())
            ->will($this->onConsecutiveCalls(true, true, false, true));
        $this->activityConfigProvider->expects($this->exactly(3))
            ->method('getConfig')
            ->will($this->onConsecutiveCalls($configs[0], $configs[1], $configs[3]));

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

        $email->expects($this->at(0))
            ->method('addActivityTarget')
            ->with($this->identicalTo($this->owners[0]));
        $email->expects($this->at(1))
            ->method('addActivityTarget')
            ->with($this->identicalTo($this->owners[3]));

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
