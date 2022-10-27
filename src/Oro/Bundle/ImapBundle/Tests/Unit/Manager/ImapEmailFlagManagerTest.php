<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\ImapBundle\Connector\ImapConnector;
use Oro\Bundle\ImapBundle\Entity\Repository\ImapEmailRepository;
use Oro\Bundle\ImapBundle\Manager\ImapEmailFlagManager;

class ImapEmailFlagManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ImapEmailFlagManager */
    private $manager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $connector;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $repoImapEmail;

    protected function setUp(): void
    {
        $this->connector = $this->createMock(ImapConnector::class);
        $this->em = $this->createMock(OroEntityManager::class);
        $this->repoImapEmail = $this->createMock(ImapEmailRepository::class);

        $this->manager = new ImapEmailFlagManager($this->connector, $this->em);
    }

    public function testSetFlags()
    {
        $folderId = 1;
        $emailId = 2;
        $flags = [];

        $this->repoImapEmail->expects($this->once())
            ->method('getUid')
            ->willReturn(1)
            ->with($folderId, $emailId);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->repoImapEmail);

        $this->connector->expects($this->once())
            ->method('setFlags')
            ->with(1, $flags);

        $emailFolder = $this->createMock(EmailFolder::class);
        $emailFolder->expects($this->once())
            ->method('getId')
            ->willReturn($folderId);

        $email = $this->createMock(Email::class);
        $email->expects($this->once())
            ->method('getId')
            ->willReturn($emailId);

        $this->manager->setFlags($emailFolder, $email, $flags);
    }

    public function testSetSeen()
    {
        $folderId = 1;
        $emailId = 2;
        $flags = [ImapEmailFlagManager::FLAG_SEEN];

        $this->repoImapEmail->expects($this->once())
            ->method('getUid')
            ->willReturn(1)
            ->with($folderId, $emailId);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->repoImapEmail);

        $this->connector->expects($this->once())
            ->method('setFlags')
            ->with(1, $flags);

        $emailFolder = $this->createMock(EmailFolder::class);
        $emailFolder->expects($this->once())
            ->method('getId')
            ->willReturn($folderId);

        $email = $this->createMock(Email::class);
        $email->expects($this->once())
            ->method('getId')
            ->willReturn($emailId);

        $this->manager->setSeen($emailFolder, $email);
    }

    public function testSetUnSeen()
    {
        $folderId = 1;
        $emailId = 2;
        $flags = [ImapEmailFlagManager::FLAG_UNSEEN];

        $this->repoImapEmail->expects($this->once())
            ->method('getUid')
            ->willReturn(1)
            ->with($folderId, $emailId);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->repoImapEmail);

        $this->connector->expects($this->once())
            ->method('setFlags')
            ->with(1, $flags);

        $emailFolder = $this->createMock(EmailFolder::class);
        $emailFolder->expects($this->once())
            ->method('getId')
            ->willReturn($folderId);

        $email = $this->createMock(Email::class);
        $email->expects($this->once())
            ->method('getId')
            ->willReturn($emailId);

        $this->manager->setUnseen($emailFolder, $email);
    }
}
