<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager\DTO;

use Oro\Bundle\ImapBundle\Manager\ImapEmailFlagManager;

class ImapEmailFlagManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ImapEmailFlagManager */
    private $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $connector;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $repoImapEmail;

    protected function setUp()
    {
        $this->connector = $this->getMockBuilder('Oro\Bundle\ImapBundle\Connector\ImapConnector')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\OroEntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repoImapEmail = $this->getMockBuilder('Oro\Bundle\ImapBundle\Entity\Repository\ImapEmailRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new ImapEmailFlagManager($this->connector, $this->em);
    }

    public function testSetFlags()
    {
        $folderId = 1;
        $emailId = 2;
        $flags = [];

        $this->repoImapEmail->expects($this->once())
            ->method('getUid')
            ->will($this->returnValue(1))
            ->with($folderId, $emailId);

        $this->em->expects($this->once())
            ->method('getRepository')->willReturn($this->repoImapEmail);

        $this->connector->expects($this->once())
            ->method('setFlags')
            ->with(1, $flags);

        $emailFolder = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\EmailFolder')
            ->disableOriginalConstructor()
            ->getMock();
        $emailFolder->expects($this->once())
            ->method('getId')->will($this->returnValue($folderId));

        $email = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Email')
            ->disableOriginalConstructor()
            ->getMock();
        $email->expects($this->once())
            ->method('getId')->will($this->returnValue($emailId));

        $this->manager->setFlags($emailFolder, $email, $flags);
    }

    public function testSetSeen()
    {
        $folderId = 1;
        $emailId = 2;
        $flags = [ImapEmailFlagManager::FLAG_SEEN];

        $this->repoImapEmail->expects($this->once())
            ->method('getUid')
            ->will($this->returnValue(1))
            ->with($folderId, $emailId);

        $this->em->expects($this->once())
            ->method('getRepository')->willReturn($this->repoImapEmail);

        $this->connector->expects($this->once())
            ->method('setFlags')
            ->with(1, $flags);

        $emailFolder = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\EmailFolder')
            ->disableOriginalConstructor()
            ->getMock();
        $emailFolder->expects($this->once())
            ->method('getId')->will($this->returnValue($folderId));

        $email = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Email')
            ->disableOriginalConstructor()
            ->getMock();
        $email->expects($this->once())
            ->method('getId')->will($this->returnValue($emailId));

        $this->manager->setSeen($emailFolder, $email);
    }

    public function testSetUnSeen()
    {
        $folderId = 1;
        $emailId = 2;
        $flags = [ImapEmailFlagManager::FLAG_UNSEEN];

        $this->repoImapEmail->expects($this->once())
            ->method('getUid')
            ->will($this->returnValue(1))
            ->with($folderId, $emailId);

        $this->em->expects($this->once())
            ->method('getRepository')->willReturn($this->repoImapEmail);

        $this->connector->expects($this->once())
            ->method('setFlags')
            ->with(1, $flags);

        $emailFolder = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\EmailFolder')
            ->disableOriginalConstructor()
            ->getMock();
        $emailFolder->expects($this->once())
            ->method('getId')->will($this->returnValue($folderId));

        $email = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Email')
            ->disableOriginalConstructor()
            ->getMock();
        $email->expects($this->once())
            ->method('getId')->will($this->returnValue($emailId));

        $this->manager->setUnseen($emailFolder, $email);
    }
}
