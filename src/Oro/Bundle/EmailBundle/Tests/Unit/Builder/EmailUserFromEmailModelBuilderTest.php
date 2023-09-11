<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Builder;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBatchProcessor;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Builder\EmailUserFromEmailModelBuilder;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment as EmailAttachmentModel;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EmailBundle\Provider\ParentMessageIdProvider;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EmailUserFromEmailModelBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var EmailEntityBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $emailEntityBuilder;

    /** @var EmailOriginHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $emailOriginHelper;

    /** @var ParentMessageIdProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $parentMessageIdProvider;

    /** @var ActivityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $activityManager;

    /** @var EmailUserFromEmailModelBuilder */
    private $emailUserFromEmailModelBuilder;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->emailEntityBuilder = $this->createMock(EmailEntityBuilder::class);
        $this->emailOriginHelper = $this->createMock(EmailOriginHelper::class);
        $this->parentMessageIdProvider = $this->createMock(ParentMessageIdProvider::class);
        $this->activityManager = $this->createMock(ActivityManager::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(Email::class)
            ->willReturn($this->entityManager);

        $this->emailUserFromEmailModelBuilder = new EmailUserFromEmailModelBuilder(
            $doctrine,
            $this->emailEntityBuilder,
            $this->emailOriginHelper,
            $this->parentMessageIdProvider,
            $this->activityManager
        );
    }

    private function getEmailModel(): EmailModel
    {
        $emailModel = new EmailModel();
        $emailModel->setFrom('from@example.com');
        $emailModel->setTo(['to1@example.com', 'to2@example.com']);
        $emailModel->setCc(['cc1@example.com', 'cc2@example.com']);
        $emailModel->setBcc(['bcc1@example.com', 'bcc2@example.com']);
        $emailModel->setSubject('sample_subject');
        $emailModel->setBody('sample_body');

        return $emailModel;
    }

    private function getEmailFolder(string $type): EmailFolder
    {
        $folder = new EmailFolder();
        $folder->setType($type);

        return $folder;
    }

    private function getFirstEmailUserFolder(EmailUser $emailUser): ?EmailFolder
    {
        $firstEmailUserFolder = $emailUser->getFolders()->first();

        return false !== $firstEmailUserFolder ? $firstEmailUserFolder : null;
    }

    /**
     * @dataProvider createFromEmailModelDataProvider
     */
    public function testCreateFromEmailModel(
        string $type,
        bool $isHtml,
        string $messageId
    ): void {
        $organization = new Organization();
        $emailModel = $this->getEmailModel();
        $emailModel->setType($type);
        $emailModel->setOrganization($organization);

        $emailEntity = new Email();
        $emailUser = new EmailUser();
        $emailUser->setEmail($emailEntity);

        $this->emailEntityBuilder->expects(self::once())
            ->method('emailUser')
            ->with(
                $emailModel->getSubject(),
                $emailModel->getFrom(),
                $emailModel->getTo(),
                self::isInstanceOf(\DateTime::class),
                self::isInstanceOf(\DateTime::class),
                self::isInstanceOf(\DateTime::class),
                Email::NORMAL_IMPORTANCE,
                $emailModel->getCc(),
                $emailModel->getBcc(),
                null,
                $organization
            )
            ->willReturn($emailUser);

        $emailBody = new EmailBody();
        $this->emailEntityBuilder->expects(self::once())
            ->method('body')
            ->with($emailModel->getBody(), $isHtml, true)
            ->willReturn($emailBody);

        $this->emailEntityBuilder->expects(self::never())
            ->method('addEmailAttachmentEntity');

        $parentMessageId = '<parent/message/id@example.org>';
        $this->parentMessageIdProvider->expects(self::once())
            ->method('getParentMessageIdToReply')
            ->with($emailModel)
            ->willReturn($parentMessageId);

        $actualEmailUser = $this->emailUserFromEmailModelBuilder
            ->createFromEmailModel($emailModel, $messageId);

        self::assertTrue($actualEmailUser->isSeen());
        self::assertEquals($messageId, $actualEmailUser->getEmail()->getMessageId());
        self::assertSame($emailBody, $actualEmailUser->getEmail()->getEmailBody());
        self::assertEquals([$parentMessageId], $actualEmailUser->getEmail()->getRefs());
        self::assertEmpty($actualEmailUser->getEmail()->getEmailBody()->getAttachments());
    }

    public function createFromEmailModelDataProvider(): array
    {
        return [
            'if type is html, then isHtml true' => [
                'type' => 'html',
                'isHtml' => true,
                'messageId' => '',
            ],
            'if type is not html, then isHtml false' => [
                'type' => 'text',
                'isHtml' => false,
                'messageId' => '',
            ],
            'with messageId' => [
                'type' => 'text',
                'isHtml' => false,
                'messageId' => 'sample/message/id@example.org',
            ],
        ];
    }

    public function testCreateFromEmailModelWithSentAt(): void
    {
        $emailModel = $this->getEmailModel();
        $emailEntity = new Email();
        $emailUser = new EmailUser();
        $emailUser->setEmail($emailEntity);
        $sentAt = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->emailEntityBuilder->expects(self::once())
            ->method('emailUser')
            ->with(
                $emailModel->getSubject(),
                $emailModel->getFrom(),
                $emailModel->getTo(),
                $sentAt,
                $sentAt,
                $sentAt,
                Email::NORMAL_IMPORTANCE,
                $emailModel->getCc(),
                $emailModel->getBcc()
            )
            ->willReturn($emailUser);

        $emailBody = new EmailBody();
        $this->emailEntityBuilder->expects(self::once())
            ->method('body')
            ->with($emailModel->getBody(), false, true)
            ->willReturn($emailBody);

        $this->emailEntityBuilder->expects(self::never())
            ->method('addEmailAttachmentEntity');

        $actualEmailUser = $this->emailUserFromEmailModelBuilder
            ->createFromEmailModel($emailModel, '', $sentAt);

        self::assertTrue($actualEmailUser->isSeen());
        self::assertEquals('', $actualEmailUser->getEmail()->getMessageId());
        self::assertSame($emailBody, $actualEmailUser->getEmail()->getEmailBody());
        self::assertEmpty($actualEmailUser->getEmail()->getRefs());
    }

    public function testCreateFromEmailModelWithAttachments(): void
    {
        $emailModel = $this->getEmailModel();
        $emailAttachment1 = new EmailAttachment();
        $emailAttachment2 = new EmailAttachment();
        $emailAttachmentModel1 = new EmailAttachmentModel();
        $emailAttachmentModel1->setEmailAttachment($emailAttachment1);
        $emailAttachmentModel2 = new EmailAttachmentModel();
        $emailAttachmentModel2->setEmailAttachment($emailAttachment2);
        $emailModel->addAttachment($emailAttachmentModel1);
        $emailModel->addAttachment(new EmailAttachmentModel());
        $emailModel->addAttachment($emailAttachmentModel2);

        $sentAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $emailEntity = new Email();
        $emailUser = new EmailUser();
        $emailUser->setEmail($emailEntity);

        $this->emailEntityBuilder->expects(self::once())
            ->method('emailUser')
            ->with(
                $emailModel->getSubject(),
                $emailModel->getFrom(),
                $emailModel->getTo(),
                $sentAt,
                $sentAt,
                $sentAt,
                Email::NORMAL_IMPORTANCE,
                $emailModel->getCc(),
                $emailModel->getBcc()
            )
            ->willReturn($emailUser);

        $emailBody = new EmailBody();
        $this->emailEntityBuilder->expects(self::once())
            ->method('body')
            ->with($emailModel->getBody(), false, true)
            ->willReturn($emailBody);

        $this->emailEntityBuilder->expects(self::exactly(2))
            ->method('addEmailAttachmentEntity')
            ->withConsecutive([$emailBody, $emailAttachment1], [$emailBody, $emailAttachment2]);

        $actualEmailUser = $this->emailUserFromEmailModelBuilder
            ->createFromEmailModel($emailModel, '', $sentAt);

        self::assertTrue($actualEmailUser->isSeen());
        self::assertSame($emailBody, $actualEmailUser->getEmail()->getEmailBody());
        self::assertEmpty($actualEmailUser->getEmail()->getRefs());
    }

    /**
     * @dataProvider setEmailOriginWhenInternalEmailOriginDataProvider
     */
    public function testSetEmailOriginWhenInternalEmailOrigin(EmailOrigin $emailOrigin): void
    {
        $emailEntity = new Email();
        $emailUser = new EmailUser();
        $emailUser->setEmail($emailEntity);

        $this->emailOriginHelper->expects(self::never())
            ->method('getEmailOrigin');

        $this->emailUserFromEmailModelBuilder->setEmailOrigin($emailUser, $emailOrigin);

        self::assertSame($emailOrigin, $emailUser->getOrigin());
        self::assertSame($emailOrigin->getOwner(), $emailUser->getOwner());
        self::assertSame($emailOrigin->getOrganization(), $emailUser->getOrganization());
        self::assertSame($emailOrigin->getFolder(FolderType::SENT), $this->getFirstEmailUserFolder($emailUser));
    }

    public function setEmailOriginWhenInternalEmailOriginDataProvider(): array
    {
        $inboxFolder = $this->getEmailFolder(FolderType::INBOX);
        $sentFolder = $this->getEmailFolder(FolderType::SENT);

        return [
            'empty origin' => [new InternalEmailOrigin()],
            'origin with owner' => [(new InternalEmailOrigin())->setOwner(new User())],
            'origin with organization' => [(new InternalEmailOrigin())->setOrganization(new Organization())],
            'origin with inbox folder' => [(new InternalEmailOrigin())->addFolder($inboxFolder)],
            'origin with sent folder' => [(new InternalEmailOrigin())->addFolder($sentFolder)],
        ];
    }

    public function testSetEmailOriginWhenEmailUserOriginWithoutMailbox(): void
    {
        $emailEntity = new Email();
        $emailUser = new EmailUser();
        $emailUser->setEmail($emailEntity);

        $emailOrigin = new UserEmailOrigin();
        $emailOrigin->addFolder($this->getEmailFolder(FolderType::SENT));

        $this->emailOriginHelper->expects(self::never())
            ->method('getEmailOrigin');

        $this->emailUserFromEmailModelBuilder->setEmailOrigin($emailUser, $emailOrigin);

        self::assertSame($emailOrigin, $emailUser->getOrigin());
        self::assertSame($emailOrigin->getOwner(), $emailUser->getOwner());
        self::assertSame($emailOrigin->getOrganization(), $emailUser->getOrganization());
        self::assertSame($emailOrigin->getFolder(FolderType::SENT), $this->getFirstEmailUserFolder($emailUser));
    }

    public function testSetEmailOriginWhenEmailUserOriginWithMailbox(): void
    {
        $emailEntity = new Email();
        $emailUser = new EmailUser();
        $emailUser->setEmail($emailEntity);

        $sentFolder = $this->getEmailFolder(FolderType::SENT);
        $emailOrigin = new UserEmailOrigin();
        $emailOrigin->addFolder($sentFolder);
        $emailOrigin->setMailbox(new Mailbox());

        $this->emailOriginHelper->expects(self::never())
            ->method('getEmailOrigin');

        $this->emailUserFromEmailModelBuilder->setEmailOrigin($emailUser, $emailOrigin);

        self::assertSame($emailOrigin, $emailUser->getOrigin());
        self::assertNull($emailUser->getOwner());
        self::assertSame($emailOrigin->getMailbox(), $emailUser->getMailboxOwner());
        self::assertSame($emailOrigin->getOrganization(), $emailUser->getOrganization());
        self::assertSame($emailOrigin->getFolder(FolderType::SENT), $this->getFirstEmailUserFolder($emailUser));
    }

    /**
     * @dataProvider setEmailOriginWhenEmailUserOriginWithoutFolderDataProvider
     */
    public function testSetEmailOriginWhenEmailUserOriginWithoutFolder(?EmailOrigin $internalEmailOrigin): void
    {
        $emailEntity = new Email();
        $emailEntity->setFromEmailAddress($this->createMock(EmailAddress::class));
        $emailUser = new EmailUser();
        $emailUser->setEmail($emailEntity);

        $emailOrigin = new UserEmailOrigin();

        $this->emailOriginHelper->expects(self::once())
            ->method('getEmailOrigin')
            ->with($emailEntity->getFromEmailAddress()->getEmail(), null, InternalEmailOrigin::BAP, false)
            ->willReturn($internalEmailOrigin);

        $this->emailUserFromEmailModelBuilder->setEmailOrigin($emailUser, $emailOrigin);

        self::assertSame($emailOrigin, $emailUser->getOrigin());
        self::assertSame($emailOrigin->getOwner(), $emailUser->getOwner());
        self::assertSame($emailOrigin->getOrganization(), $emailUser->getOrganization());
        self::assertSame(
            $internalEmailOrigin?->getFolder(FolderType::SENT),
            $this->getFirstEmailUserFolder($emailUser)
        );
    }

    public function setEmailOriginWhenEmailUserOriginWithoutFolderDataProvider(): array
    {
        $inboxFolder = $this->getEmailFolder(FolderType::INBOX);
        $sentFolder = $this->getEmailFolder(FolderType::SENT);

        return [
            'origin with inbox folder' => [(new InternalEmailOrigin())->addFolder($inboxFolder)],
            'origin with sent folder' => [(new InternalEmailOrigin())->addFolder($sentFolder)],
        ];
    }

    public function testSetEmailOriginWhenEmailUserOriginWithoutFolderAndNoInternal(): void
    {
        $emailEntity = new Email();
        $emailEntity->setFromEmailAddress($this->createMock(EmailAddress::class));
        $emailUser = new EmailUser();
        $emailUser->setEmail($emailEntity);

        $emailOrigin = new UserEmailOrigin();

        $this->emailOriginHelper->expects(self::once())
            ->method('getEmailOrigin')
            ->with($emailEntity->getFromEmailAddress()->getEmail(), null, InternalEmailOrigin::BAP, false)
            ->willReturn(null);

        $this->emailUserFromEmailModelBuilder->setEmailOrigin($emailUser, $emailOrigin);

        self::assertSame($emailOrigin, $emailUser->getOrigin());
        self::assertSame($emailOrigin->getOwner(), $emailUser->getOwner());
        self::assertSame($emailOrigin->getOrganization(), $emailUser->getOrganization());
        self::assertEmpty($emailUser->getFolders());
    }

    public function testAddActivityEntitiesWhenNoEntities(): void
    {
        $this->activityManager->expects(self::never())
            ->method('addActivityTarget');

        $this->emailUserFromEmailModelBuilder->addActivityEntities(new EmailUser(), []);
    }

    public function testAddActivityEntities(): void
    {
        $emailEntity = new Email();
        $emailEntity->setFromEmailAddress($this->createMock(EmailAddress::class));
        $emailUser = new EmailUser();
        $emailUser->setEmail($emailEntity);

        $activityEntity1 = new \stdClass();
        $activityEntity2 = new \stdClass();

        $this->activityManager->expects(self::exactly(2))
            ->method('addActivityTarget')
            ->withConsecutive([$emailEntity, $activityEntity1], [$emailEntity, $activityEntity2]);

        $this->emailUserFromEmailModelBuilder->addActivityEntities($emailUser, [$activityEntity1, $activityEntity2]);
    }

    public function testPersistAndFlush(): void
    {
        $batch = $this->createMock(EmailEntityBatchProcessor::class);
        $this->emailEntityBuilder->expects(self::once())
            ->method('getBatch')
            ->willReturn($batch);

        $batch->expects(self::once())
            ->method('persist');

        $this->entityManager->expects(self::once())
            ->method('flush');

        $this->emailEntityBuilder->expects(self::once())
            ->method('clear');

        $this->emailUserFromEmailModelBuilder->persistAndFlush();
    }
}
