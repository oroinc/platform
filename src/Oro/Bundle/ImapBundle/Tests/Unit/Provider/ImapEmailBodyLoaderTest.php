<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Exception\EmailBodyNotFoundException;
use Oro\Bundle\ImapBundle\Entity\ImapEmail;
use Oro\Bundle\ImapBundle\Entity\Repository\ImapEmailRepository;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\DTO\Email as EmailDTO;
use Oro\Bundle\ImapBundle\Manager\DTO\EmailAttachment as EmailAttachmentDTO;
use Oro\Bundle\ImapBundle\Manager\DTO\EmailBody as EmailBodyDTO;
use Oro\Bundle\ImapBundle\Manager\ImapEmailManager;
use Oro\Bundle\ImapBundle\Manager\ImapEmailManagerFactory;
use Oro\Bundle\ImapBundle\Provider\ImapEmailBodyLoader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImapEmailBodyLoaderTest extends TestCase
{
    private ImapEmailManagerFactory&MockObject $imapEmailManagerFactory;
    private ConfigManager&MockObject $configManager;
    private EmailFolder&MockObject $emailFolder;
    private ImapEmailManager&MockObject $imapEmailManager;
    private EntityManagerInterface&MockObject $entityManager;
    private EmailDTO&MockObject $emailDTO;
    private ImapEmailBodyLoader $imapEmailBodyLoader;


    #[\Override]
    protected function setUp(): void
    {
        $this->imapEmailManagerFactory = $this->createMock(ImapEmailManagerFactory::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->emailFolder = $this->createMock(EmailFolder::class);
        $this->imapEmailManager = $this->createMock(ImapEmailManager::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->emailDTO = $this->createMock(EmailDTO::class);

        $emailOrigin = $this->createMock(UserEmailOrigin::class);

        $this->emailFolder->expects(self::once())
            ->method('getOrigin')
            ->willReturn($emailOrigin);

        $this->imapEmailManagerFactory->expects(self::once())
            ->method('getImapEmailManager')
            ->with($emailOrigin)
            ->willReturn($this->imapEmailManager);

        $this->emailFolder->expects(self::once())
            ->method('getFullName')
            ->willReturn('INBOX');

        $this->imapEmailManager->expects(self::once())
            ->method('selectFolder')
            ->with('INBOX');

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('getSingleScalarResult')
            ->willReturn('uuid');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('select')
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::once())
            ->method('innerJoin')
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::once())
            ->method('where')
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::exactly(2))
            ->method('setParameter')
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $imapEmailRepository = $this->createMock(ImapEmailRepository::class);
        $imapEmailRepository->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->entityManager->expects(self::once())
            ->method('getRepository')
            ->with(ImapEmail::class)
            ->willReturn($imapEmailRepository);


        $this->imapEmailBodyLoader = new ImapEmailBodyLoader(
            $this->imapEmailManagerFactory,
            $this->configManager,
        );
    }

    public function testLoadEmailBody(): void
    {
        $email = $this->createMock(Email::class);
        $emailBodyDTO = $this->createMock(EmailBodyDTO::class);

        $this->imapEmailManager->expects(self::once())
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

        $this->emailDTO->expects(self::once())
            ->method('getAttachments')
            ->willReturn([$attachment1, $attachment2, $attachment3]);

        $this->emailDTO->expects(self::exactly(2))
            ->method('getBody')
            ->willReturn($emailBodyDTO);

        $emailBodyDTO->expects(self::once())
            ->method('getContent')
            ->willReturn('some content');

        $emailBodyDTO->expects(self::once())
            ->method('getBodyIsText')
            ->willReturn(false);

        $emailBody = $this->imapEmailBodyLoader->loadEmailBody($this->emailFolder, $email, $this->entityManager);
        self::assertInstanceOf(EmailBody::class, $emailBody);
        self::assertCount(3, $emailBody->getAttachments());
        self::assertTrue($emailBody->getHasAttachments());
        self::assertEquals('some content', $emailBody->getBodyContent());
    }

    public function testLoadEmailBodyWithException(): void
    {
        $email = $this->createMock(Email::class);
        $email->expects(self::once())
            ->method('getSubject')
            ->willReturn('test@test.com');

        $this->imapEmailManager->expects(self::once())
            ->method('findEmail')
            ->with('uuid')
            ->willReturn(null);

        $this->expectException(EmailBodyNotFoundException::class);
        $this->expectExceptionMessage('Cannot find a body for "test@test.com" email.');

        $this->imapEmailBodyLoader->loadEmailBody($this->emailFolder, $email, $this->entityManager);
    }
}
