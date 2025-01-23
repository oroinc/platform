<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManager;
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
    /** @var ImapEmailManagerFactory|MockObject*/
    private $imapEmailManagerFactory;

    /** @var EntityManager|MockObject */
    private $entityManager;

    /** @var EmailDTO|MockObject */
    private $emailDTO;

    /** @var ImapEmailAttachmentLoader  */
    private $imapEmailAttachmentLoader;

    #[\Override]
    protected function setUp(): void
    {
        $this->imapEmailManagerFactory = $this->createMock(ImapEmailManagerFactory::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $imapEmailRepository = $this->createMock(ImapEmailRepository::class);
        $imapEmail = $this->createMock(ImapEmail::class);
        $this->emailDTO = $this->createMock(EmailDTO::class);
        $imapEmailManager = $this->createMock(ImapEmailManager::class);
        $imapEmailFolder = $this->createMock(ImapEmailFolder::class);
        $emailFolder = $this->createMock(EmailFolder::class);
        $emailOrigin = $this->createMock(UserEmailOrigin::class);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(ImapEmail::class)
            ->willReturn($imapEmailRepository);

        $imapEmailRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($imapEmail);

        $imapEmail->expects($this->once())
            ->method('getImapFolder')
            ->willReturn($imapEmailFolder);

        $imapEmail->expects($this->once())
            ->method('getUid')
            ->willReturn('uuid');

        $imapEmailFolder->expects($this->once())
            ->method('getFolder')
            ->willReturn($emailFolder);

        $emailFolder->expects($this->once())
            ->method('getOrigin')
            ->willReturn($emailOrigin);

        $this->imapEmailManagerFactory->expects($this->once())
            ->method('getImapEmailManager')
            ->with($emailOrigin)
            ->willReturn($imapEmailManager);

        $imapEmailManager->expects($this->once())
            ->method('findEmail')
            ->with('uuid')
            ->willReturn($this->emailDTO);

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

        $this->emailDTO->expects($this->exactly(2))
            ->method('getAttachments')
            ->willReturn([$attachment1, $attachment2, $attachment3]);

        $this->imapEmailAttachmentLoader = new ImapEmailAttachmentLoader(
            $this->imapEmailManagerFactory,
            $this->entityManager,
        );
    }

    public function testLoadEmailAttachments()
    {
        $emailBody = new EmailBody();
        $emailBody->setEmail(new Email());

        $emailAttachments = $this->imapEmailAttachmentLoader->loadEmailAttachments($emailBody);
        $this->assertEquals(3, count($emailAttachments));
        $this->assertEquals('fileName1', $emailAttachments[0]->getFileName());
        $this->assertEquals('fileName2', $emailAttachments[1]->getFileName());
        $this->assertEquals('fileName3', $emailAttachments[2]->getFileName());
    }

    public function testLoadEmailAttachment()
    {
        $emailBody = new EmailBody();
        $emailBody->setEmail(new Email());

        $emailAttachment = $this->imapEmailAttachmentLoader->loadEmailAttachment($emailBody, 'fileName1');
        $this->assertInstanceOf(EmailAttachment::class, $emailAttachment);
        $this->assertEquals('fileName1', $emailAttachment->getFileName());
    }
}
