<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Event\Handler;

use Oro\Bundle\NotificationBundle\Entity\RecipientList;
use Oro\Bundle\NotificationBundle\Event\Handler\EmailNotificationAdapter;
use Oro\Bundle\NotificationBundle\Tests\Unit\Event\Handler\Stub\EmailHolderStub;
use Oro\Component\Testing\Unit\EntityTrait;

class EmailNotificationAdapterTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var EmailNotificationAdapter */
    private $adapter;

    /** @var EmailHolderStub */
    private $entity;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $emailNotification;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $configProvider;

    protected function setUp()
    {
        $this->entity = new EmailHolderStub();
        $this->emailNotification = $this->getMockBuilder('Oro\Bundle\NotificationBundle\Entity\EmailNotification')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->adapter = new EmailNotificationAdapter(
            $this->entity,
            $this->emailNotification,
            $this->em,
            $this->configProvider,
            $this->getPropertyAccessor()
        );
    }

    protected function tearDown()
    {
        unset($this->adapter);
        unset($this->entity);
        unset($this->emailNotification);
        unset($this->em);
    }

    public function testGetTemplate()
    {
        $template = $this->createMock('Oro\Bundle\EmailBundle\Entity\EmailTemplate');

        $this->emailNotification->expects($this->once())
            ->method('getTemplate')
            ->will($this->returnValue($template));

        $this->assertEquals($template, $this->adapter->getTemplate());
    }

    public function testGetRecipientEmails()
    {
        $emails = ["email"];
        $recipientList = new RecipientList();
        $repo = $this->getMockBuilder('Oro\Bundle\NotificationBundle\Entity\Repository\RecipientListRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailNotification->expects($this->once())
            ->method('getRecipientList')
            ->will($this->returnValue($recipientList));
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with('Oro\Bundle\NotificationBundle\Entity\RecipientList')
            ->will($this->returnValue($repo));
        $repo->expects($this->once())
            ->method('getRecipientEmails')
            ->with($this->identicalTo($recipientList), $this->identicalTo($this->entity))
            ->will($this->returnValue($emails));

        $this->assertEquals($emails, $this->adapter->getRecipientEmails());
    }

    public function testGetRecipientEmailsFromAdditionalAssociations()
    {
        $expectedEmails = [
            'test1@example.com',
            'test2@example.com',
            'test3@example.com',
            'test4@example.com',
        ];

        $subHolder1 = new EmailHolderStub('test1@example.com');
        $subHolder2 = new EmailHolderStub('test2@example.com');
        $subHolder3 = new EmailHolderStub('test3@example.com');
        $subHolder4 = new EmailHolderStub('test4@example.com');
        $subHolder3->setHolder($subHolder4);

        $this->entity->setHolder($subHolder1);
        $this->entity->setHolders([
            $subHolder2,
            $subHolder3,
        ]);

        $recipientList = new RecipientList();
        $recipientList->setAdditionalEmailAssociations([
            'holder',
            'holders',
            'holders.holder',
        ]);

        $repo = $this->getMockBuilder('Oro\Bundle\NotificationBundle\Entity\Repository\RecipientListRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailNotification->expects($this->once())
            ->method('getRecipientList')
            ->will($this->returnValue($recipientList));
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with('Oro\Bundle\NotificationBundle\Entity\RecipientList')
            ->will($this->returnValue($repo));
        $repo->expects($this->once())
            ->method('getRecipientEmails')
            ->with($this->identicalTo($recipientList), $this->identicalTo($this->entity))
            ->will($this->returnValue([]));

        $this->assertEquals($expectedEmails, $this->adapter->getRecipientEmails());
    }
}
