<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Builder;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\DependencyInjection\Configuration as AttachmentConfiguration;
use Oro\Bundle\AttachmentBundle\Provider\FileConstraintsProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Builder\EmailModelBuilder;
use Oro\Bundle\EmailBundle\Builder\Helper\EmailModelBuilderHelper;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment;
use Oro\Bundle\EmailBundle\Form\Model\Factory;
use Oro\Bundle\EmailBundle\Provider\EmailActivityListProvider;
use Oro\Bundle\EmailBundle\Provider\EmailAttachmentProvider;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class EmailModelBuilderTest extends TestCase
{
    private EmailModelBuilderHelper&MockObject $helper;
    private ManagerRegistry&MockObject $doctrine;
    private ConfigManager&MockObject $configManager;
    private EmailActivityListProvider&MockObject $activityListProvider;
    private EmailAttachmentProvider&MockObject $emailAttachmentProvider;
    private RequestStack $requestStack;
    private HtmlTagHelper&MockObject $htmlTagHelper;
    private FileConstraintsProvider&MockObject $fileConstraintsProvider;
    private Email&MockObject $email;
    private EmailModelBuilder $emailModelBuilder;

    #[\Override]
    protected function setUp(): void
    {
        $this->helper = $this->createMock(EmailModelBuilderHelper::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->activityListProvider = $this->createMock(EmailActivityListProvider::class);
        $this->emailAttachmentProvider = $this->createMock(EmailAttachmentProvider::class);
        $this->requestStack = new RequestStack();
        $this->htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $this->fileConstraintsProvider = $this->createMock(FileConstraintsProvider::class);
        $this->email = $this->createMock(Email::class);

        $this->emailAttachmentProvider->expects(self::any())
            ->method('getThreadAttachments')
            ->willReturn([]);

        $this->email->expects(self::any())
            ->method('getActivityTargets')
            ->willReturn([]);

        $this->emailModelBuilder = new EmailModelBuilder(
            $this->helper,
            $this->doctrine,
            $this->configManager,
            $this->activityListProvider,
            $this->emailAttachmentProvider,
            new Factory(),
            $this->requestStack,
            $this->htmlTagHelper,
            $this->fileConstraintsProvider
        );
    }

    public function testCreateEmailModelWithEmptyData(): void
    {
        $request = new Request();
        $request->setMethod('GET');
        $this->requestStack->push($request);

        $this->helper->expects(self::never())
            ->method('decodeClassName');

        $this->doctrine->expects(self::never())
            ->method('getRepository');

        $this->helper->expects(self::never())
            ->method('preciseFullEmailAddress');

        $this->helper->expects(self::once())
            ->method('getUser')
            ->willReturn($this->createMock(User::class));

        $this->helper->expects(self::once())
            ->method('buildFullEmailAddress');

        $this->helper->expects(self::never())
            ->method('getTargetEntity');

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_email.signature')
            ->willReturn('Signature');
        $this->htmlTagHelper->expects(self::once())
            ->method('sanitize')
            ->with('Signature')
            ->willReturn('Sanitized Signature');

        $emailModel = new EmailModel();
        $result = $this->emailModelBuilder->createEmailModel($emailModel);

        self::assertSame($emailModel, $result);
        self::assertNull($result->getEntityClass());
        self::assertNull($result->getEntityId());
        self::assertNull($result->getSubject());
        self::assertNull($result->getFrom());
        self::assertSame([], $result->getTo());
        self::assertSame([], $result->getCc());
        self::assertSame([], $result->getBcc());
        self::assertEquals('Sanitized Signature', $result->getSignature());
        self::assertSame([], array_values($result->getAttachmentsAvailable()));
        self::assertCount(0, $result->getContexts());
    }

    public function testCreateEmailModel(): void
    {
        $entityClass = User::class;
        $entityId = 1;
        $from = 'from@example.com';
        $to = 'to@example.com';
        $subject = 'Subject';

        $request = new Request();
        $request->setMethod('GET');
        $request->query->set('entityClass', $entityClass);
        $request->query->set('entityId', $entityId);
        $request->query->set('from', $from);
        $request->query->set('to', $to);
        $request->query->set('cc', $to);
        $request->query->set('bcc', $to);
        $request->query->set('subject', $subject);
        $this->requestStack->push($request);

        $this->helper->expects(self::once())
            ->method('decodeClassName')
            ->willReturn($entityClass);

        $repository = $this->createMock(EntityRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        $bigAttachment = new EmailAttachment();
        $bigAttachment->setFileName('big.jpg');
        $bigAttachment->setMimeType('image/jpeg');
        $bigAttachment->setFileSize(20 * AttachmentConfiguration::BYTES_MULTIPLIER);
        $unsupportedMimeTypeAttachment = new EmailAttachment();
        $unsupportedMimeTypeAttachment->setFileName('image.webp');
        $unsupportedMimeTypeAttachment->setMimeType('image/webp');
        $unsupportedMimeTypeAttachment->setFileSize(2 * AttachmentConfiguration::BYTES_MULTIPLIER);
        $allowedAttachment = new EmailAttachment();
        $allowedAttachment->setFileName('image.jpg');
        $allowedAttachment->setMimeType('image/jpeg');
        $allowedAttachment->setFileSize(2 * AttachmentConfiguration::BYTES_MULTIPLIER);
        $scopeEntity = new User();
        $repository->expects(self::once())
            ->method('find')
            ->with($entityId)
            ->willReturn($scopeEntity);
        $this->emailAttachmentProvider->expects(self::once())
            ->method('getScopeEntityAttachments')
            ->with($scopeEntity)
            ->willReturn([$bigAttachment, $unsupportedMimeTypeAttachment, $allowedAttachment]);
        $this->fileConstraintsProvider->expects(self::any())
            ->method('getMaxSizeByConfigPath')
            ->with('oro_email.attachment_max_size')
            ->willReturn(10 * AttachmentConfiguration::BYTES_MULTIPLIER);
        $this->fileConstraintsProvider->expects(self::any())
            ->method('getMimeTypes')
            ->willReturn(['image/jpeg']);

        $this->helper->expects(self::exactly(4))
            ->method('preciseFullEmailAddress');

        $this->helper->expects(self::never())
            ->method('getUser');

        $this->helper->expects(self::never())
            ->method('buildFullEmailAddress');

        $contextEntity = $this->createMock($entityClass);
        $this->helper->expects(self::once())
            ->method('getTargetEntity')
            ->with($entityClass, $entityId)
            ->willReturn($contextEntity);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_email.signature')
            ->willReturn('Signature');
        $this->htmlTagHelper->expects(self::once())
            ->method('sanitize')
            ->with('Signature')
            ->willReturn('Sanitized Signature');

        $emailModel = new EmailModel();
        $result = $this->emailModelBuilder->createEmailModel($emailModel);

        self::assertSame($emailModel, $result);
        self::assertEquals($entityClass, $result->getEntityClass());
        self::assertEquals($entityId, $result->getEntityId());
        self::assertEquals($subject, $result->getSubject());
        self::assertEquals($from, $result->getFrom());
        self::assertEquals([$to], $result->getTo());
        self::assertEquals([$to], $result->getCc());
        self::assertEquals([$to], $result->getBcc());
        self::assertEquals('Sanitized Signature', $result->getSignature());
        self::assertEquals([$allowedAttachment], array_values($result->getAttachmentsAvailable()));
        self::assertCount(1, $result->getContexts());
        self::assertSame($contextEntity, $result->getContexts()->first());
    }

    public function testCreateEmailModelWhenEmptyContextsRequested(): void
    {
        $entityClass = User::class;
        $entityId = 1;
        $from = 'from@example.com';
        $to = 'to@example.com';
        $subject = 'Subject';

        $request = new Request();
        $request->setMethod('GET');
        $request->query->set('entityClass', $entityClass);
        $request->query->set('entityId', $entityId);
        $request->query->set('emptyContexts', true);
        $request->query->set('from', $from);
        $request->query->set('to', $to);
        $request->query->set('cc', $to);
        $request->query->set('bcc', $to);
        $request->query->set('subject', $subject);
        $this->requestStack->push($request);

        $this->helper->expects(self::once())
            ->method('decodeClassName')
            ->willReturn($entityClass);

        $repository = $this->createMock(EntityRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        $bigAttachment = new EmailAttachment();
        $bigAttachment->setFileName('big.jpg');
        $bigAttachment->setMimeType('image/jpeg');
        $bigAttachment->setFileSize(20 * AttachmentConfiguration::BYTES_MULTIPLIER);
        $unsupportedMimeTypeAttachment = new EmailAttachment();
        $unsupportedMimeTypeAttachment->setFileName('image.webp');
        $unsupportedMimeTypeAttachment->setMimeType('image/webp');
        $unsupportedMimeTypeAttachment->setFileSize(2 * AttachmentConfiguration::BYTES_MULTIPLIER);
        $allowedAttachment = new EmailAttachment();
        $allowedAttachment->setFileName('image.jpg');
        $allowedAttachment->setMimeType('image/jpeg');
        $allowedAttachment->setFileSize(2 * AttachmentConfiguration::BYTES_MULTIPLIER);
        $scopeEntity = new User();
        $repository->expects(self::once())
            ->method('find')
            ->with($entityId)
            ->willReturn($scopeEntity);
        $this->emailAttachmentProvider->expects(self::once())
            ->method('getScopeEntityAttachments')
            ->with($scopeEntity)
            ->willReturn([$bigAttachment, $unsupportedMimeTypeAttachment, $allowedAttachment]);
        $this->fileConstraintsProvider->expects(self::any())
            ->method('getMaxSizeByConfigPath')
            ->with('oro_email.attachment_max_size')
            ->willReturn(10 * AttachmentConfiguration::BYTES_MULTIPLIER);
        $this->fileConstraintsProvider->expects(self::any())
            ->method('getMimeTypes')
            ->willReturn(['image/jpeg']);

        $this->helper->expects(self::exactly(4))
            ->method('preciseFullEmailAddress');

        $this->helper->expects(self::never())
            ->method('getUser');

        $this->helper->expects(self::never())
            ->method('buildFullEmailAddress');

        $this->helper->expects(self::never())
            ->method('getTargetEntity');

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_email.signature')
            ->willReturn('Signature');
        $this->htmlTagHelper->expects(self::once())
            ->method('sanitize')
            ->with('Signature')
            ->willReturn('Sanitized Signature');

        $emailModel = new EmailModel();
        $result = $this->emailModelBuilder->createEmailModel($emailModel);

        self::assertSame($emailModel, $result);
        self::assertEquals($entityClass, $result->getEntityClass());
        self::assertEquals($entityId, $result->getEntityId());
        self::assertEquals($subject, $result->getSubject());
        self::assertEquals($from, $result->getFrom());
        self::assertEquals([$to], $result->getTo());
        self::assertEquals([$to], $result->getCc());
        self::assertEquals([$to], $result->getBcc());
        self::assertEquals('Sanitized Signature', $result->getSignature());
        self::assertEquals([$allowedAttachment], array_values($result->getAttachmentsAvailable()));
        self::assertCount(0, $result->getContexts());
    }

    /**
     * @dataProvider createReplyEmailModelProvider
     */
    public function testCreateReplyEmailModel(object $getOwnerResult, object $getUserResult, int $getToCalls): void
    {
        $fromEmailAddress = $this->createMock(EmailAddress::class);

        $fromEmailAddress->expects(self::once())
            ->method('getOwner')
            ->willReturn($getOwnerResult);

        $this->helper->expects(self::any())
            ->method('getUser')
            ->willReturn($getUserResult);

        $getUserResult->expects(self::any())
            ->method('getEmails')
            ->willReturn([]);

        $this->email->expects(self::once())
            ->method('getFromEmailAddress')
            ->willReturn($fromEmailAddress);

        $this->email->expects(self::any())
            ->method('getId');

        $emailAddress = $this->createMock(EmailAddress::class);
        $emailAddress->expects(self::exactly($getToCalls))
            ->method('getEmail')
            ->willReturn(null);

        $emailRecipient = $this->createMock(EmailRecipient::class);
        $emailRecipient->expects(self::exactly($getToCalls))
            ->method('getEmailAddress')
            ->willReturn($emailAddress);

        $to = new ArrayCollection();
        $to->add($emailRecipient);

        $this->email->expects(self::exactly($getToCalls))
            ->method('getTo')
            ->willReturn($to);

        $this->helper->expects(self::once())
            ->method('prependWith');

        $this->helper->expects(self::once())
            ->method('getEmailBody');
        $this->activityListProvider->expects(self::once())
            ->method('getTargetEntities')
            ->willReturn([]);

        $result = $this->emailModelBuilder->createReplyEmailModel($this->email);
        self::assertInstanceOf(EmailModel::class, $result);
    }

    public function createReplyEmailModelProvider(): array
    {
        $entityOne = $this->createMock(User::class);
        $entityTwo = $this->createMock(User::class);

        return [
            [$entityOne, $entityTwo, 1],
            [$entityTwo, $entityOne, 1],
            [$entityOne, $entityOne, 1],
            [$entityTwo, $entityTwo, 1],
        ];
    }

    public function testCreateForwardEmailModel(): void
    {
        $request = new Request();
        $this->emailModelBuilder->setRequest($request);

        $this->helper->expects(self::once())
            ->method('prependWith');

        $emailBody = $this->createMock(EmailBody::class);
        $emailBody->expects(self::once())
            ->method('getAttachments')
            ->willReturn([]);

        $this->email->expects(self::once())
            ->method('getEmailBody')
            ->willReturn($emailBody);

        $result = $this->emailModelBuilder->createForwardEmailModel($this->email);
        self::assertInstanceOf(EmailModel::class, $result);
    }

    /**
     * @dataProvider createReplyEmailModelProvider
     */
    public function testCreateReplyAllEmailModel(object $getOwnerResult, object $getUserResult, int $getToCalls): void
    {
        $fromEmailAddress = $this->createMock(EmailAddress::class);
        $fromCcEmailAddress = $this->createMock(EmailAddress::class);

        $fromEmailAddress->expects(self::once())
            ->method('getOwner')
            ->willReturn($getOwnerResult);

        $this->helper->expects(self::any())
            ->method('getUser')
            ->willReturn($getUserResult);

        $getUserResult->expects(self::any())
            ->method('getEmails')
            ->willReturn([]);

        $this->email->expects(self::once())
            ->method('getFromEmailAddress')
            ->willReturn($fromEmailAddress);

        $this->email->expects(self::any())
            ->method('getId');

        $emailAddress = $this->createMock(EmailAddress::class);
        $emailAddress->expects(self::exactly($getToCalls))
            ->method('getEmail')
            ->willReturn(null);

        $emailRecipient = $this->createMock(EmailRecipient::class);
        $emailRecipient->expects(self::exactly($getToCalls))
            ->method('getEmailAddress')
            ->willReturn($emailAddress);

        $to = new ArrayCollection();
        $to->add($emailRecipient);

        $this->email->expects(self::exactly($getToCalls))
            ->method('getTo')
            ->willReturn($to);

        $emailCcRecipient = $this->createMock(EmailRecipient::class);
        $emailCcRecipient->expects(self::once())
            ->method('getEmailAddress')
            ->willReturn($fromCcEmailAddress);

        $cc = new ArrayCollection();
        $cc->add($emailCcRecipient);

        $this->email->expects(self::exactly($getToCalls))
            ->method('getCc')
            ->willReturn($cc);

        $this->helper->expects(self::once())
            ->method('prependWith');

        $this->helper->expects(self::once())
            ->method('getEmailBody');
        $this->activityListProvider->expects(self::once())
            ->method('getTargetEntities')
            ->willReturn([]);

        $result = $this->emailModelBuilder->createReplyAllEmailModel($this->email);
        self::assertInstanceOf(EmailModel::class, $result);
    }
}
