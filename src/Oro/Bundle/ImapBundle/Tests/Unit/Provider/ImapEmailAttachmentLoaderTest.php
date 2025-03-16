<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\ImapBundle\Entity\ImapEmail;
use Oro\Bundle\ImapBundle\Entity\ImapEmailFolder;
use Oro\Bundle\ImapBundle\Entity\Repository\ImapEmailRepository;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\DTO\Email as EmailDTO;
use Oro\Bundle\ImapBundle\Manager\DTO\EmailAttachment as EmailAttachmentDTO;
use Oro\Bundle\ImapBundle\Manager\ImapEmailManager;
use Oro\Bundle\ImapBundle\Manager\ImapEmailManagerFactory;
use Oro\Bundle\ImapBundle\Provider\ImapEmailAttachmentLoader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImapEmailAttachmentLoaderTest extends TestCase
{
    private ImapEmailManagerFactory&MockObject $imapEmailManagerFactory;
    private ManagerRegistry&MockObject $doctrine;
    private ImapEmailAttachmentLoader $imapEmailAttachmentLoader;

    #[\Override]
    protected function setUp(): void
    {
        $this->imapEmailManagerFactory = $this->createMock(ImapEmailManagerFactory::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $emailDTO = $this->createMock(EmailDTO::class);
        $emailOrigin = $this->createMock(UserEmailOrigin::class);

        $emailFolder = $this->createMock(EmailFolder::class);
        $emailFolder->expects(self::once())
            ->method('getOrigin')
            ->willReturn($emailOrigin);

        $imapEmailFolder = $this->createMock(ImapEmailFolder::class);
        $imapEmailFolder->expects(self::once())
            ->method('getFolder')
            ->willReturn($emailFolder);

        $imapEmail = $this->createMock(ImapEmail::class);
        $imapEmail->expects(self::once())
            ->method('getUid')
            ->willReturn('uuid');
        $imapEmail->expects(self::once())
            ->method('getImapFolder')
            ->willReturn($imapEmailFolder);

        $imapEmailRepository = $this->createMock(ImapEmailRepository::class);
        $imapEmailRepository->expects(self::once())
            ->method('findOneBy')
            ->willReturn($imapEmail);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(ImapEmail::class)
            ->willReturn($imapEmailRepository);

        $imapEmailManager = $this->createMock(ImapEmailManager::class);
        $imapEmailManager->expects(self::once())
            ->method('findEmail')
            ->with('uuid')
            ->willReturn($emailDTO);

        $this->imapEmailManagerFactory->expects(self::once())
            ->method('getImapEmailManager')
            ->with($emailOrigin)
            ->willReturn($imapEmailManager);

        $attachment1 = new EmailAttachmentDTO();
        $attachment1->setFileName('fileName1')
            ->setContent('someContent')
            ->setContentType('image/jpg')
            ->setFileSize(234234)
            ->setContentTransferEncoding('base64')
            ->setContentId('3423');

        $attachment2 = new EmailAttachmentDTO();
        $attachment2->setFileName('fileName2')
            ->setContent('someContent2')
            ->setContentType('image/png')
            ->setFileSize(2343541)
            ->setContentTransferEncoding('base64')
            ->setContentId('23');

        $attachment3 = new EmailAttachmentDTO();
        $attachment3->setFileName('fileName3')
            ->setContent('someContent3')
            ->setContentType('image/png')
            ->setFileSize(2321)
            ->setContentTransferEncoding('base64')
            ->setContentId('12');

        $emailDTO->expects(self::exactly(2))
            ->method('getAttachments')
            ->willReturn([$attachment1, $attachment2, $attachment3]);

        $this->imapEmailAttachmentLoader = new ImapEmailAttachmentLoader(
            $this->imapEmailManagerFactory,
            $this->doctrine
        );
    }

    public function testLoadEmailAttachments(): void
    {
        $emailBody = new EmailBody();
        $emailBody->setEmail(new Email());

        $emailAttachments = $this->imapEmailAttachmentLoader->loadEmailAttachments($emailBody);
        self::assertCount(3, $emailAttachments);
        self::assertEquals('fileName1', $emailAttachments[0]->getFileName());
        self::assertEquals('fileName2', $emailAttachments[1]->getFileName());
        self::assertEquals('fileName3', $emailAttachments[2]->getFileName());
    }

    public function testLoadEmailAttachment(): void
    {
        $emailBody = new EmailBody();
        $emailBody->setEmail(new Email());

        $emailAttachment = $this->imapEmailAttachmentLoader->loadEmailAttachment($emailBody, 'fileName1');
        self::assertInstanceOf(EmailAttachment::class, $emailAttachment);
        self::assertEquals('fileName1', $emailAttachment->getFileName());
    }
}
