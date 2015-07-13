<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Manager;

use Oro\Bundle\EmailBundle\Entity\Manager\EmailManager;

class EmailManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var EmailManager */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $emailThreadManager;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $emailThreadProvider;

    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailThreadManager = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\EmailThreadManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailThreadProvider = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->manager = new EmailManager($this->em, $this->emailThreadManager, $this->emailThreadProvider);
    }

    public function testSetEmailSeenNothingChanges()
    {
        $emailUser = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailUser');
        $emailUser->expects($this->once())
            ->method('isSeen')
            ->will($this->returnValue(true));
        $emailUser->expects($this->never())
            ->method('setSeen');
        $this->emailThreadManager->expects($this->never())
            ->method('updateThreadHead');
        $this->em->expects($this->never())
            ->method('flush');
        $this->manager->setEmailUserSeen($emailUser);
    }

    public function testSetEmailSeenChanges()
    {
        $emailUser = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailUser');
        $emailUser->expects($this->once())
            ->method('isSeen')
            ->will($this->returnValue(false));
        $emailUser->expects($this->once())
            ->method('setSeen')
            ->with(true);
        $this->em->expects($this->once())
            ->method('flush');

        $this->manager->setEmailUserSeen($emailUser);
    }
}
