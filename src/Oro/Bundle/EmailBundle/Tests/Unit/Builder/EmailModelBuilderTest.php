<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Builder;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class EmailModelBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailModelBuilder */
    private $emailModelBuilder;

    /** @var EmailModelBuilderHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $helper;

    /** @var RequestStack */
    private $requestStack;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var EmailActivityListProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $activityListProvider;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var EmailAttachmentProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $emailAttachmentProvider;

    /** @var Email|\PHPUnit\Framework\MockObject\MockObject */
    private $email;

    /** @var HtmlTagHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $htmlTagHelper;

    /** @var FileConstraintsProvider */
    private $fileConstraintsProvider;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->helper = $this->createMock(EmailModelBuilderHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->activityListProvider = $this->createMock(EmailActivityListProvider::class);
        $this->emailAttachmentProvider = $this->createMock(EmailAttachmentProvider::class);
        $this->htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $this->fileConstraintsProvider = $this->createMock(FileConstraintsProvider::class);
        $this->email = $this->createMock(Email::class);
        $this->requestStack = new RequestStack();

        $this->emailAttachmentProvider->expects($this->any())
            ->method('getThreadAttachments')
            ->willReturn([]);

        $this->email->expects($this->any())
            ->method('getActivityTargets')
            ->willReturn([]);

        $this->emailModelBuilder = new EmailModelBuilder(
            $this->helper,
            $this->entityManager,
            $this->configManager,
            $this->activityListProvider,
            $this->emailAttachmentProvider,
            new Factory(),
            $this->requestStack,
            $this->htmlTagHelper,
            $this->fileConstraintsProvider
        );
    }

    /**
     * @dataProvider createEmailModelProvider
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreateEmailModel(
        ?string $entityClass,
        ?int $entityId,
        ?string $from,
        ?string $to,
        ?string $subject,
        int $helperDecodeClassNameCalls,
        int $emGetRepositoryCalls,
        int $helperPreciseFullEmailAddressCalls,
        int $helperGetUserCalls,
        int $helperBuildFullEmailAddress
    ) {
        $emailModel = new EmailModel();

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

        $this->helper->expects($this->exactly($helperDecodeClassNameCalls))
            ->method('decodeClassName')
            ->willReturn($entityClass);

        $repository = $this->createMock(EntityRepository::class);
        $this->entityManager->expects($this->exactly($emGetRepositoryCalls))
            ->method('getRepository')
            ->willReturn($repository);
        $expectedAttachments = [];
        if ($emGetRepositoryCalls) {
            $maxSize = 10 * AttachmentConfiguration::BYTES_MULTIPLIER;
            $allowedMimeTypes = ['image/jpeg'];
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
            $attachments = [
                $bigAttachment,
                $unsupportedMimeTypeAttachment,
                $allowedAttachment
            ];
            $expectedAttachments = [$allowedAttachment];
            $scopeEntity = new User();
            $repository->expects($this->once())
                ->method('find')
                ->with($entityId)
                ->willReturn($scopeEntity);
            $this->emailAttachmentProvider->expects($this->once())
                ->method('getScopeEntityAttachments')
                ->with($scopeEntity)
                ->willReturn($attachments);
            $this->fileConstraintsProvider->expects($this->any())
                ->method('getMaxSizeByConfigPath')
                ->with('oro_email.attachment_max_size')
                ->willReturn($maxSize);
            $this->fileConstraintsProvider->expects($this->any())
                ->method('getMimeTypes')
                ->willReturn($allowedMimeTypes);
        }

        $this->helper->expects($this->exactly($helperPreciseFullEmailAddressCalls))
            ->method('preciseFullEmailAddress');

        $this->helper->expects($this->exactly($helperGetUserCalls))
            ->method('getUser')
            ->willReturn($this->createMock(User::class));

        $this->helper->expects($this->exactly($helperBuildFullEmailAddress))
            ->method('buildFullEmailAddress');

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_email.signature')
            ->willReturn('Signature');
        $this->htmlTagHelper->expects($this->once())
            ->method('sanitize')
            ->with('Signature')
            ->willReturn('Sanitized Signature');

        $result = $this->emailModelBuilder->createEmailModel($emailModel);
        $this->assertEquals($emailModel, $result);

        $this->assertEquals($entityClass, $result->getEntityClass());
        $this->assertEquals($entityId, $result->getEntityId());
        $this->assertEquals($subject, $result->getSubject());
        $this->assertEquals($from, $result->getFrom());

        $expected = $to ? [$to] : [];
        $this->assertEquals($expected, $result->getTo());
        $this->assertEquals($expected, $result->getCc());
        $this->assertEquals($expected, $result->getBcc());
        $this->assertEquals('Sanitized Signature', $result->getSignature());
        $this->assertEquals($expectedAttachments, array_values($result->getAttachmentsAvailable()));
    }

    public function createEmailModelProvider(): array
    {
        return [
            [
                'entityClass' => User::class,
                'entityId' => 1,
                'from' => 'from@example.com',
                'to' => 'to@example.com',
                'subject' => 'Subject',
                'helperDecodeClassNameCalls' => 1,
                'emGetRepositoryCalls' => 1,
                'helperPreciseFullEmailAddressCalls' => 4,
                'helperGetUserCalls' => 0,
                'helperBuildFullEmailAddress' => 0,
            ],
            [
                'entityClass' => null,
                'entityId' => null,
                'from' => null,
                'to' => null,
                'subject' => null,
                'helperDecodeClassNameCalls' => 0,
                'emGetRepositoryCalls' => 0,
                'helperPreciseFullEmailAddressCalls' => 0,
                'helperGetUserCalls' => 1,
                'helperBuildFullEmailAddress' => 1,
            ],
        ];
    }

    /**
     * @dataProvider createReplyEmailModelProvider
     */
    public function testCreateReplyEmailModel(object $getOwnerResult, object $getUserResult, int $getToCalls)
    {
        $fromEmailAddress = $this->createMock(EmailAddress::class);

        $fromEmailAddress->expects($this->once())
            ->method('getOwner')
            ->willReturn($getOwnerResult);

        $this->helper->expects($this->any())
            ->method('getUser')
            ->willReturn($getUserResult);

        $getUserResult->expects($this->any())
            ->method('getEmails')
            ->willReturn([]);

        $this->email->expects($this->once())
            ->method('getFromEmailAddress')
            ->willReturn($fromEmailAddress);

        $this->email->expects($this->any())
            ->method('getId');

        $emailAddress = $this->createMock(EmailAddress::class);
        $emailAddress->expects($this->exactly($getToCalls))
            ->method('getEmail')
            ->willReturn(null);

        $emailRecipient = $this->createMock(EmailRecipient::class);
        $emailRecipient->expects($this->exactly($getToCalls))
            ->method('getEmailAddress')
            ->willReturn($emailAddress);

        $to = new ArrayCollection();
        $to->add($emailRecipient);

        $this->email->expects($this->exactly($getToCalls))
            ->method('getTo')
            ->willReturn($to);

        $this->helper->expects($this->once())
            ->method('prependWith');

        $this->helper->expects($this->once())
            ->method('getEmailBody');
        $this->activityListProvider->expects($this->once())
            ->method('getTargetEntities')
            ->willReturn([]);

        $result = $this->emailModelBuilder->createReplyEmailModel($this->email);
        $this->assertInstanceOf(EmailModel::class, $result);
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

    public function testCreateForwardEmailModel()
    {
        $request = new Request();
        $this->emailModelBuilder->setRequest($request);

        $this->helper->expects($this->once())
            ->method('prependWith');

        $emailBody = $this->createMock(EmailBody::class);
        $emailBody->expects($this->once())
            ->method('getAttachments')
            ->willReturn([]);

        $this->email->expects($this->once())
            ->method('getEmailBody')
            ->willReturn($emailBody);

        $result = $this->emailModelBuilder->createForwardEmailModel($this->email);
        $this->assertInstanceOf(EmailModel::class, $result);
    }

    /**
     * @dataProvider createReplyEmailModelProvider
     */
    public function testCreateReplyAllEmailModel(object $getOwnerResult, object $getUserResult, int $getToCalls)
    {
        $fromEmailAddress = $this->createMock(EmailAddress::class);
        $fromCcEmailAddress = $this->createMock(EmailAddress::class);

        $fromEmailAddress->expects($this->once())
            ->method('getOwner')
            ->willReturn($getOwnerResult);

        $this->helper->expects($this->any())
            ->method('getUser')
            ->willReturn($getUserResult);

        $getUserResult->expects($this->any())
            ->method('getEmails')
            ->willReturn([]);

        $this->email->expects($this->once())
            ->method('getFromEmailAddress')
            ->willReturn($fromEmailAddress);

        $this->email->expects($this->any())
            ->method('getId');

        $emailAddress = $this->createMock(EmailAddress::class);
        $emailAddress->expects($this->exactly($getToCalls))
            ->method('getEmail')
            ->willReturn(null);

        $emailRecipient = $this->createMock(EmailRecipient::class);
        $emailRecipient->expects($this->exactly($getToCalls))
            ->method('getEmailAddress')
            ->willReturn($emailAddress);

        $to = new ArrayCollection();
        $to->add($emailRecipient);

        $this->email->expects($this->exactly($getToCalls))
            ->method('getTo')
            ->willReturn($to);

        $emailCcRecipient = $this->createMock(EmailRecipient::class);
        $emailCcRecipient->expects($this->once())
            ->method('getEmailAddress')
            ->willReturn($fromCcEmailAddress);

        $cc = new ArrayCollection();
        $cc->add($emailCcRecipient);

        $this->email->expects($this->exactly($getToCalls))
            ->method('getCc')
            ->willReturn($cc);

        $this->helper->expects($this->once())
            ->method('prependWith');

        $this->helper->expects($this->once())
            ->method('getEmailBody');
        $this->activityListProvider->expects($this->once())
            ->method('getTargetEntities')
            ->willReturn([]);

        $result = $this->emailModelBuilder->createReplyAllEmailModel($this->email);
        $this->assertInstanceOf(EmailModel::class, $result);
    }
}
