<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
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
    /** @var ImapEmailManagerFactory|MockObject*/
    private $imapEmailManagerFactory;

    /** @var ConfigManager|MockObject */
    private $configManager;

    /** @var EntityManager|MockObject */
    private $entityManager;

    /** @var EmailFolder|MockObject */
    private $emailFolder;

    /** @var ImapEmailBodyLoader  */
    private $imapEmailBodyLoader;

    /** @var ImapEmailManager|MockObject */
    private $imapEmailManager;


    #[\Override]
    protected function setUp(): void
    {
        $this->imapEmailManagerFactory = $this->createMock(ImapEmailManagerFactory::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->emailFolder = $this->createMock(EmailFolder::class);
        $emailOrigin = $this->createMock(UserEmailOrigin::class);
        $this->imapEmailManager = $this->createMock(ImapEmailManager::class);
        $imapEmailRepository = $this->createMock(ImapEmailRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $this->emailDTO = $this->createMock(EmailDTO::class);

        $this->emailFolder->expects($this->once())
            ->method('getOrigin')
            ->willReturn($emailOrigin);

        $this->imapEmailManagerFactory->expects($this->once())
            ->method('getImapEmailManager')
            ->with($emailOrigin)
            ->willReturn($this->imapEmailManager);

        $this->emailFolder->expects($this->once())
            ->method('getFullName')
            ->willReturn('INBOX');

        $this->imapEmailManager->expects($this->once())
            ->method('selectFolder')
            ->with('INBOX');

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(ImapEmail::class)
            ->willReturn($imapEmailRepository);

        $imapEmailRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())->method('select')->willReturn($queryBuilder);
        $queryBuilder->expects($this->once())->method('innerJoin')->willReturn($queryBuilder);
        $queryBuilder->expects($this->once())->method('where')->willReturn($queryBuilder);
        $queryBuilder->expects($this->exactly(2))->method('setParameter')->willReturn($queryBuilder);
        $queryBuilder->expects($this->once())->method('getQuery')->willReturn($query);

        $query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn('uuid');


        $this->imapEmailBodyLoader = new ImapEmailBodyLoader(
            $this->imapEmailManagerFactory,
            $this->configManager,
        );
    }

    public function testLoadEmailBody()
    {
        $email = $this->createMock(Email::class);
        $emailBodyDTO = $this->createMock(EmailBodyDTO::class);

        $this->imapEmailManager->expects($this->once())
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

        $this->emailDTO->expects($this->once())
            ->method('getAttachments')
            ->willReturn([$attachment1, $attachment2, $attachment3]);

        $this->emailDTO->expects($this->exactly(2))
            ->method('getBody')
            ->willReturn($emailBodyDTO);

        $emailBodyDTO->expects($this->once())
            ->method('getContent')
            ->willReturn('some content');

        $emailBodyDTO->expects($this->once())
            ->method('getBodyIsText')
            ->willReturn(false);

        $emailBody = $this->imapEmailBodyLoader->loadEmailBody($this->emailFolder, $email, $this->entityManager);
        $this->assertInstanceOf(EmailBody::class, $emailBody);
        $this->assertEquals(3, count($emailBody->getAttachments()));
        $this->assertTrue($emailBody->getHasAttachments());
        $this->assertEquals('some content', $emailBody->getBodyContent());
    }

    public function testLoadEmailBodyWithException()
    {
        $email = $this->createMock(Email::class);
        $email->expects($this->once())
            ->method('getSubject')
            ->willReturn('test@test.com');

        $this->imapEmailManager->expects($this->once())
            ->method('findEmail')
            ->with('uuid')
            ->willReturn(null);

        $this->expectException(EmailBodyNotFoundException::class);
        $this->expectExceptionMessage('Cannot find a body for "test@test.com" email.');

        $this->imapEmailBodyLoader->loadEmailBody($this->emailFolder, $email, $this->entityManager);
    }
}
