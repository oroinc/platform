<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Builder;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBatchInterface;
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
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
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
    /** @var EmailEntityBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $emailEntityBuilder;

    /** @var EmailOriginHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $emailOriginHelper;

    /** @var ParentMessageIdProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $parentMessageIdProvider;

    /** @var EmailActivityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $emailActivityManager;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var EmailUserFromEmailModelBuilder */
    private $emailUserFromEmailModelBuilder;

    protected function setUp(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->emailEntityBuilder = $this->createMock(EmailEntityBuilder::class);
        $this->emailOriginHelper = $this->createMock(EmailOriginHelper::class);
        $this->parentMessageIdProvider = $this->createMock(ParentMessageIdProvider::class);
        $this->emailActivityManager = $this->createMock(EmailActivityManager::class);

        $this->emailUserFromEmailModelBuilder = new EmailUserFromEmailModelBuilder(
            $managerRegistry,
            $this->emailEntityBuilder,
            $this->emailOriginHelper,
            $this->parentMessageIdProvider,
            $this->emailActivityManager
        );

        $this->entityManager = $this->createMock(EntityManager::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getManagerForClass')
            ->with(Email::class)
            ->willReturn($this->entityManager);
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
        $emailModel = ($this->createEmailModel())
            ->setType($type)
            ->setOrganization($organization);

        $emailEntity = new Email();
        $emailUser = (new EmailUser())->setEmail($emailEntity);

        $this->emailEntityBuilder
            ->expects(self::once())
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
        $this->emailEntityBuilder
            ->expects(self::once())
            ->method('body')
            ->with($emailModel->getBody(), $isHtml, true)
            ->willReturn($emailBody);

        $this->emailEntityBuilder
            ->expects(self::never())
            ->method('addEmailAttachmentEntity');

        $parentMessageId = '<parent/message/id@example.org>';
        $this->parentMessageIdProvider
            ->expects(self::once())
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
        $emailModel = $this->createEmailModel();
        $emailEntity = new Email();
        $emailUser = (new EmailUser())->setEmail($emailEntity);
        $sentAt = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->emailEntityBuilder
            ->expects(self::once())
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
        $this->emailEntityBuilder
            ->expects(self::once())
            ->method('body')
            ->with($emailModel->getBody(), false, true)
            ->willReturn($emailBody);

        $this->emailEntityBuilder
            ->expects(self::never())
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
        $emailModel = $this->createEmailModel();
        $emailAttachment1 = new EmailAttachment();
        $emailAttachment2 = new EmailAttachment();
        $emailModel->addAttachment((new EmailAttachmentModel())->setEmailAttachment($emailAttachment1));
        $emailModel->addAttachment(new EmailAttachmentModel());
        $emailModel->addAttachment((new EmailAttachmentModel())->setEmailAttachment($emailAttachment2));

        $sentAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $emailEntity = new Email();
        $emailUser = (new EmailUser())->setEmail($emailEntity);

        $this->emailEntityBuilder
            ->expects(self::once())
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
        $this->emailEntityBuilder
            ->expects(self::once())
            ->method('body')
            ->with($emailModel->getBody(), false, true)
            ->willReturn($emailBody);

        $this->emailEntityBuilder
            ->expects(self::exactly(2))
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
        $emailUser = (new EmailUser())
            ->setEmail($emailEntity);

        $this->emailOriginHelper
            ->expects(self::never())
            ->method('getEmailOrigin');

        $this->emailUserFromEmailModelBuilder->setEmailOrigin($emailUser, $emailOrigin);

        self::assertSame($emailOrigin, $emailUser->getOrigin());
        self::assertSame($emailOrigin->getOwner(), $emailUser->getOwner());
        self::assertSame($emailOrigin->getOrganization(), $emailUser->getOrganization());
        self::assertSame($emailOrigin->getFolder(FolderType::SENT), $emailUser->getFolders()->first());
    }

    public function setEmailOriginWhenInternalEmailOriginDataProvider(): array
    {
        $inboxFolder = (new EmailFolder())->setType(FolderType::INBOX);
        $sentFolder = (new EmailFolder())->setType(FolderType::SENT);

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
        $emailUser = (new EmailUser())->setEmail($emailEntity);

        $emailOrigin = (new UserEmailOrigin())
            ->addFolder((new EmailFolder())->setType(FolderType::SENT));

        $this->emailOriginHelper
            ->expects(self::never())
            ->method('getEmailOrigin');

        $this->emailUserFromEmailModelBuilder->setEmailOrigin($emailUser, $emailOrigin);

        self::assertSame($emailOrigin, $emailUser->getOrigin());
        self::assertSame($emailOrigin->getOwner(), $emailUser->getOwner());
        self::assertSame($emailOrigin->getOrganization(), $emailUser->getOrganization());
        self::assertSame($emailOrigin->getFolder(FolderType::SENT), $emailUser->getFolders()->first());
    }

    public function testSetEmailOriginWhenEmailUserOriginWithMailbox(): void
    {
        $emailEntity = new Email();
        $emailUser = (new EmailUser())->setEmail($emailEntity);

        $sentFolder = (new EmailFolder())->setType(FolderType::SENT);
        $emailOrigin = (new UserEmailOrigin())
            ->addFolder($sentFolder)
            ->setMailbox(new Mailbox());

        $this->emailOriginHelper
            ->expects(self::never())
            ->method('getEmailOrigin');

        $this->emailUserFromEmailModelBuilder->setEmailOrigin($emailUser, $emailOrigin);

        self::assertSame($emailOrigin, $emailUser->getOrigin());
        self::assertNull($emailUser->getOwner());
        self::assertSame($emailOrigin->getMailbox(), $emailUser->getMailboxOwner());
        self::assertSame($emailOrigin->getOrganization(), $emailUser->getOrganization());
        self::assertSame($emailOrigin->getFolder(FolderType::SENT), $emailUser->getFolders()->first());
    }

    /**
     * @dataProvider setEmailOriginWhenEmailUserOriginWithoutFolderDataProvider
     */
    public function testSetEmailOriginWhenEmailUserOriginWithoutFolder(?EmailOrigin $internalEmailOrigin): void
    {
        $emailEntity = (new Email())->setFromEmailAddress($this->createMock(EmailAddress::class));
        $emailUser = (new EmailUser())->setEmail($emailEntity);

        $emailOrigin = (new UserEmailOrigin());

        $this->emailOriginHelper
            ->expects(self::once())
            ->method('getEmailOrigin')
            ->with($emailEntity->getFromEmailAddress()->getEmail(), null, InternalEmailOrigin::BAP, false)
            ->willReturn($internalEmailOrigin);

        $this->emailUserFromEmailModelBuilder->setEmailOrigin($emailUser, $emailOrigin);

        self::assertSame($emailOrigin, $emailUser->getOrigin());
        self::assertSame($emailOrigin->getOwner(), $emailUser->getOwner());
        self::assertSame($emailOrigin->getOrganization(), $emailUser->getOrganization());
        self::assertSame($internalEmailOrigin?->getFolder(FolderType::SENT), $emailUser->getFolders()->first());
    }

    public function setEmailOriginWhenEmailUserOriginWithoutFolderDataProvider(): array
    {
        $inboxFolder = (new EmailFolder())->setType(FolderType::INBOX);
        $sentFolder = (new EmailFolder())->setType(FolderType::SENT);

        return [
            'origin with inbox folder' => [(new InternalEmailOrigin())->addFolder($inboxFolder)],
            'origin with sent folder' => [(new InternalEmailOrigin())->addFolder($sentFolder)],
        ];
    }

    public function testSetEmailOriginWhenEmailUserOriginWithoutFolderAndNoInternal(): void
    {
        $emailEntity = (new Email())->setFromEmailAddress($this->createMock(EmailAddress::class));
        $emailUser = (new EmailUser())->setEmail($emailEntity);

        $emailOrigin = (new UserEmailOrigin());

        $this->emailOriginHelper
            ->expects(self::once())
            ->method('getEmailOrigin')
            ->with($emailEntity->getFromEmailAddress()->getEmail(), null, InternalEmailOrigin::BAP, false)
            ->willReturn(null);

        $this->emailUserFromEmailModelBuilder->setEmailOrigin($emailUser, $emailOrigin);

        self::assertSame($emailOrigin, $emailUser->getOrigin());
        self::assertSame($emailOrigin->getOwner(), $emailUser->getOwner());
        self::assertSame($emailOrigin->getOrganization(), $emailUser->getOrganization());
        self::assertEmpty($emailUser->getFolders());
    }

    private function createEmailModel(): EmailModel
    {
        return (new EmailModel())
            ->setFrom('from@example.com')
            ->setTo(['to1@example.com', 'to2@example.com'])
            ->setCc(['cc1@example.com', 'cc2@example.com'])
            ->setBcc(['bcc1@example.com', 'bcc2@example.com'])
            ->setSubject('sample_subject')
            ->setBody('sample_body');
    }

    public function testAddActivityEntitiesWhenNoEntities(): void
    {
        $this->emailActivityManager
            ->expects(self::never())
            ->method('addAssociation');

        $this->emailUserFromEmailModelBuilder->addActivityEntities(new EmailUser(), []);
    }

    public function testAddActivityEntities(): void
    {
        $emailEntity = (new Email())->setFromEmailAddress($this->createMock(EmailAddress::class));
        $emailUser = (new EmailUser())->setEmail($emailEntity);

        $activityEntity1 = new \stdClass();
        $activityEntity2 = new \stdClass();

        $this->emailActivityManager
            ->expects(self::exactly(2))
            ->method('addAssociation')
            ->withConsecutive([$emailEntity, $activityEntity1], [$emailEntity, $activityEntity2]);

        $this->emailUserFromEmailModelBuilder->addActivityEntities($emailUser, [$activityEntity1, $activityEntity2]);
    }

    public function testPersistAndFlush(): void
    {
        $batch = $this->createMock(EmailEntityBatchInterface::class);
        $this->emailEntityBuilder
            ->expects(self::once())
            ->method('getBatch')
            ->willReturn($batch);

        $batch
            ->expects(self::once())
            ->method('persist');

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $this->emailEntityBuilder
            ->expects(self::once())
            ->method('clear');

        $this->emailUserFromEmailModelBuilder->persistAndFlush();
    }
}
