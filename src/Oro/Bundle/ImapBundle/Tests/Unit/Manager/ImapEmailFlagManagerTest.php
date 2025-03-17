<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\ImapBundle\Connector\ImapConnector;
use Oro\Bundle\ImapBundle\Entity\Repository\ImapEmailRepository;
use Oro\Bundle\ImapBundle\Manager\ImapEmailFlagManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImapEmailFlagManagerTest extends TestCase
{
    private ImapConnector&MockObject $connector;
    private EntityManagerInterface&MockObject $em;
    private ImapEmailFlagManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->connector = $this->createMock(ImapConnector::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->manager = new ImapEmailFlagManager($this->connector, $this->em);
    }

    public function testSetFlags(): void
    {
        $folderId = 1;
        $emailId = 2;
        $flags = [];

        $repository = $this->createMock(ImapEmailRepository::class);
        $repository->expects(self::once())
            ->method('getUid')
            ->willReturn(1)
            ->with($folderId, $emailId);

        $this->em->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->connector->expects(self::once())
            ->method('setFlags')
            ->with(1, $flags);

        $emailFolder = $this->createMock(EmailFolder::class);
        $emailFolder->expects(self::once())
            ->method('getId')
            ->willReturn($folderId);

        $email = $this->createMock(Email::class);
        $email->expects(self::once())
            ->method('getId')
            ->willReturn($emailId);

        $this->manager->setFlags($emailFolder, $email, $flags);
    }

    public function testSetSeen(): void
    {
        $folderId = 1;
        $emailId = 2;
        $flags = [ImapEmailFlagManager::FLAG_SEEN];

        $repository = $this->createMock(ImapEmailRepository::class);
        $repository->expects(self::once())
            ->method('getUid')
            ->willReturn(1)
            ->with($folderId, $emailId);

        $this->em->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->connector->expects(self::once())
            ->method('setFlags')
            ->with(1, $flags);

        $emailFolder = $this->createMock(EmailFolder::class);
        $emailFolder->expects(self::once())
            ->method('getId')
            ->willReturn($folderId);

        $email = $this->createMock(Email::class);
        $email->expects(self::once())
            ->method('getId')
            ->willReturn($emailId);

        $this->manager->setSeen($emailFolder, $email);
    }

    public function testSetUnSeen(): void
    {
        $folderId = 1;
        $emailId = 2;
        $flags = [ImapEmailFlagManager::FLAG_UNSEEN];

        $repository = $this->createMock(ImapEmailRepository::class);
        $repository->expects(self::once())
            ->method('getUid')
            ->willReturn(1)
            ->with($folderId, $emailId);

        $this->em->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->connector->expects(self::once())
            ->method('setFlags')
            ->with(1, $flags);

        $emailFolder = $this->createMock(EmailFolder::class);
        $emailFolder->expects(self::once())
            ->method('getId')
            ->willReturn($folderId);

        $email = $this->createMock(Email::class);
        $email->expects(self::once())
            ->method('getId')
            ->willReturn($emailId);

        $this->manager->setUnseen($emailFolder, $email);
    }
}
