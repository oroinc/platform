<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Factory;

use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Factory\EmailAttachmentEntityFromEmailTemplateAttachmentFactory;
use Oro\Bundle\EmailBundle\Factory\EmailAttachmentModelFromEmailTemplateAttachmentFactory;
use Oro\Bundle\EmailBundle\Factory\EmailModelFromEmailTemplateFactory;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment as EmailAttachmentModel;
use Oro\Bundle\EmailBundle\Form\Model\Factory as EmailModelFactory;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateAttachmentModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Provider\EmailTemplateContextProvider;
use Oro\Bundle\EmailBundle\Provider\RenderedEmailTemplateProvider;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EmailModelFromEmailTemplateFactoryTest extends TestCase
{
    private EmailTemplateContextProvider&MockObject $emailTemplateContextProvider;
    private RenderedEmailTemplateProvider&MockObject $renderedEmailTemplateProvider;
    private EmailAttachmentModelFromEmailTemplateAttachmentFactory&MockObject $emailAttachmentModelFactory;
    private EmailAttachmentEntityFromEmailTemplateAttachmentFactory&MockObject $emailAttachmentEntityFactory;
    private EmailOriginHelper&MockObject $emailOriginHelper;
    private EntityOwnerAccessor&MockObject $entityOwnerAccessor;
    private EmailModelFromEmailTemplateFactory $factory;
    private From $from;

    #[\Override]
    protected function setUp(): void
    {
        $this->emailTemplateContextProvider = $this->createMock(EmailTemplateContextProvider::class);
        $this->renderedEmailTemplateProvider = $this->createMock(RenderedEmailTemplateProvider::class);
        $emailModelFactory = $this->createMock(EmailModelFactory::class);
        $this->emailAttachmentModelFactory =
            $this->createMock(EmailAttachmentModelFromEmailTemplateAttachmentFactory::class);
        $this->emailAttachmentEntityFactory =
            $this->createMock(EmailAttachmentEntityFromEmailTemplateAttachmentFactory::class);
        $this->emailOriginHelper = $this->createMock(EmailOriginHelper::class);
        $this->entityOwnerAccessor = $this->createMock(EntityOwnerAccessor::class);

        $this->factory = new EmailModelFromEmailTemplateFactory(
            $this->emailTemplateContextProvider,
            $this->renderedEmailTemplateProvider,
            $emailModelFactory,
            $this->emailAttachmentModelFactory,
            $this->emailAttachmentEntityFactory,
            $this->emailOriginHelper,
            $this->entityOwnerAccessor
        );

        $emailModelFactory->expects(self::any())
            ->method('getEmail')
            ->willReturn(new EmailModel());

        $this->from = From::emailAddress('no-reply@example.com');
    }

    public function testCreateEmailModelWithPlainType(): void
    {
        $recipient = (new User())->setEmail('recipient1@example.com');
        $templateName = 'sample_template';
        $templateParams = ['sample_key' => 'sample_value'];
        $templateContext = ['localization' => new Localization()];

        $this->emailTemplateContextProvider->expects(self::once())
            ->method('getTemplateContext')
            ->with($this->from, [$recipient], $templateName, $templateParams)
            ->willReturn($templateContext);

        $emailTemplateModel = (new EmailTemplateModel($templateName))
            ->setSubject('sample_subject')
            ->setContent('sample_content')
            ->setType(EmailTemplateInterface::TYPE_TEXT);

        $this->renderedEmailTemplateProvider->expects(self::once())
            ->method('findAndRenderEmailTemplate')
            ->with($templateName, $templateParams, $templateContext)
            ->willReturn($emailTemplateModel);

        $emailOrigin = $this->createMock(EmailOrigin::class);
        $this->emailOriginHelper->expects(self::once())
            ->method('getEmailOrigin')
            ->with($this->from->toString(), null)
            ->willReturn($emailOrigin);

        $emailModel = $this->factory->createEmailModel($this->from, $recipient, $templateName, $templateParams);

        self::assertEquals($this->from->toString(), $emailModel->getFrom());
        self::assertEquals($emailOrigin, $emailModel->getOrigin());
        self::assertEquals([$recipient->getEmail()], $emailModel->getTo());
        self::assertEquals($emailTemplateModel->getSubject(), $emailModel->getSubject());
        self::assertEquals($emailTemplateModel->getContent(), $emailModel->getBody());
        self::assertEquals('text', $emailModel->getType());
    }

    public function testCreateEmailModelWithSingleRecipient(): void
    {
        $recipient = (new User())->setEmail('recipient1@example.com');
        $templateName = 'sample_template';
        $templateParams = ['sample_key' => 'sample_value'];
        $templateContext = ['localization' => new Localization()];

        $this->emailTemplateContextProvider->expects(self::once())
            ->method('getTemplateContext')
            ->with($this->from, [$recipient], $templateName, $templateParams)
            ->willReturn($templateContext);

        $emailTemplateModel = (new EmailTemplateModel($templateName, 'sample_content'))
            ->setSubject('sample_subject');

        $this->renderedEmailTemplateProvider->expects(self::once())
            ->method('findAndRenderEmailTemplate')
            ->with($templateName, $templateParams, $templateContext)
            ->willReturn($emailTemplateModel);

        $emailOrigin = $this->createMock(EmailOrigin::class);
        $this->emailOriginHelper->expects(self::once())
            ->method('getEmailOrigin')
            ->with($this->from->toString(), null)
            ->willReturn($emailOrigin);

        $emailModel = $this->factory->createEmailModel($this->from, $recipient, $templateName, $templateParams);

        self::assertEquals($this->from->toString(), $emailModel->getFrom());
        self::assertEquals($emailOrigin, $emailModel->getOrigin());
        self::assertEquals([$recipient->getEmail()], $emailModel->getTo());
        self::assertEquals($emailTemplateModel->getSubject(), $emailModel->getSubject());
        self::assertEquals($emailTemplateModel->getContent(), $emailModel->getBody());
        self::assertEquals('html', $emailModel->getType());
    }

    public function testCreateEmailModelWithMultipleRecipients(): void
    {
        $recipient1 = (new User())->setEmail('recipient1@example.com');
        $recipient2 = (new User())->setEmail('recipient2@example.com');
        $templateName = 'sample_template';
        $templateParams = ['sample_key' => 'sample_value'];
        $templateContext = ['localization' => new Localization()];

        $this->emailTemplateContextProvider->expects(self::once())
            ->method('getTemplateContext')
            ->with($this->from, [$recipient1, $recipient2], $templateName, $templateParams)
            ->willReturn($templateContext);

        $emailTemplateModel = (new EmailTemplateModel($templateName, 'sample_content'))
            ->setSubject('sample_subject');

        $this->renderedEmailTemplateProvider->expects(self::once())
            ->method('findAndRenderEmailTemplate')
            ->with($templateName, $templateParams, $templateContext)
            ->willReturn($emailTemplateModel);

        $emailOrigin = $this->createMock(EmailOrigin::class);
        $this->emailOriginHelper->expects(self::once())
            ->method('getEmailOrigin')
            ->with($this->from->toString(), null)
            ->willReturn($emailOrigin);

        $emailModel = $this->factory->createEmailModel(
            $this->from,
            [$recipient1, $recipient2],
            $templateName,
            $templateParams
        );

        self::assertEquals($this->from->toString(), $emailModel->getFrom());
        self::assertEquals($emailOrigin, $emailModel->getOrigin());
        self::assertEquals([$recipient1->getEmail(), $recipient2->getEmail()], $emailModel->getTo());
        self::assertEquals($emailTemplateModel->getSubject(), $emailModel->getSubject());
        self::assertEquals($emailTemplateModel->getContent(), $emailModel->getBody());
        self::assertEquals('html', $emailModel->getType());
    }

    public function testCreateEmailModelWithEmailTemplateCriteriaAndNoEntity(): void
    {
        $recipient1 = (new User())->setEmail('recipient1@example.com');
        $templateName = 'sample_template';
        $templateParams = ['sample_key' => 'sample_value'];
        $emailTemplateCriteria = new EmailTemplateCriteria($templateName);
        $templateContext = ['localization' => new Localization()];

        $this->emailTemplateContextProvider->expects(self::once())
            ->method('getTemplateContext')
            ->with($this->from, [$recipient1], $emailTemplateCriteria, $templateParams)
            ->willReturn($templateContext);

        $emailTemplateModel = (new EmailTemplateModel($templateName, 'sample_content'))
            ->setSubject('sample_subject');

        $this->renderedEmailTemplateProvider->expects(self::once())
            ->method('findAndRenderEmailTemplate')
            ->with($emailTemplateCriteria, $templateParams, $templateContext)
            ->willReturn($emailTemplateModel);

        $emailOrigin = $this->createMock(EmailOrigin::class);
        $this->emailOriginHelper->expects(self::once())
            ->method('getEmailOrigin')
            ->with($this->from->toString(), null)
            ->willReturn($emailOrigin);

        $emailModel = $this->factory->createEmailModel(
            $this->from,
            $recipient1,
            $emailTemplateCriteria,
            $templateParams
        );

        self::assertEquals($this->from->toString(), $emailModel->getFrom());
        self::assertEquals($emailOrigin, $emailModel->getOrigin());
        self::assertEquals([$recipient1->getEmail()], $emailModel->getTo());
        self::assertEquals($emailTemplateModel->getSubject(), $emailModel->getSubject());
        self::assertEquals($emailTemplateModel->getContent(), $emailModel->getBody());
        self::assertEquals('html', $emailModel->getType());
    }

    public function testCreateEmailModelWithEmailTemplateCriteriaAndEntity(): void
    {
        $recipient1 = (new User())->setEmail('recipient1@example.com');
        $templateName = 'sample_template';
        $templateParams = ['entity' => new \stdClass(), 'sample_key' => 'sample_value'];
        $emailTemplateCriteria = new EmailTemplateCriteria($templateName, 'Acme\\Bundle\\Entity\\SampleEntity');
        $templateContext = ['localization' => new Localization()];

        $this->emailTemplateContextProvider->expects(self::once())
            ->method('getTemplateContext')
            ->with($this->from, [$recipient1], $emailTemplateCriteria, $templateParams)
            ->willReturn($templateContext);

        $emailTemplateModel = (new EmailTemplateModel($templateName, 'sample_content'))
            ->setSubject('sample_subject');

        $this->renderedEmailTemplateProvider->expects(self::once())
            ->method('findAndRenderEmailTemplate')
            ->with($emailTemplateCriteria, $templateParams, $templateContext)
            ->willReturn($emailTemplateModel);

        $organization = $this->createMock(Organization::class);
        $this->entityOwnerAccessor->expects(self::once())
            ->method('getOrganization')
            ->with($templateParams['entity'])
            ->willReturn($organization);

        $emailOrigin = $this->createMock(EmailOrigin::class);
        $this->emailOriginHelper->expects(self::once())
            ->method('getEmailOrigin')
            ->with($this->from->toString(), $organization)
            ->willReturn($emailOrigin);

        $emailModel = $this->factory->createEmailModel(
            $this->from,
            $recipient1,
            $emailTemplateCriteria,
            $templateParams
        );

        self::assertEquals($this->from->toString(), $emailModel->getFrom());
        self::assertEquals($organization, $emailModel->getOrganization());
        self::assertEquals($emailOrigin, $emailModel->getOrigin());
        self::assertEquals([$recipient1->getEmail()], $emailModel->getTo());
        self::assertEquals($emailTemplateModel->getSubject(), $emailModel->getSubject());
        self::assertEquals($emailTemplateModel->getContent(), $emailModel->getBody());
        self::assertEquals('html', $emailModel->getType());
    }

    public function testCreateEmailModelWithTemplateNameAndEntity(): void
    {
        $recipient1 = (new User())->setEmail('recipient1@example.com');
        $templateName = 'sample_template';
        $templateParams = ['entity' => new \stdClass(), 'sample_key' => 'sample_value'];
        $templateContext = ['localization' => new Localization()];

        $this->emailTemplateContextProvider->expects(self::once())
            ->method('getTemplateContext')
            ->with($this->from, [$recipient1], $templateName, $templateParams)
            ->willReturn($templateContext);

        $emailTemplateModel = (new EmailTemplateModel($templateName, 'sample_content'))
            ->setSubject('sample_subject');

        $this->renderedEmailTemplateProvider->expects(self::once())
            ->method('findAndRenderEmailTemplate')
            ->with($templateName, $templateParams, $templateContext)
            ->willReturn($emailTemplateModel);

        $organization = $this->createMock(Organization::class);
        $this->entityOwnerAccessor->expects(self::once())
            ->method('getOrganization')
            ->with($templateParams['entity'])
            ->willReturn($organization);

        $emailOrigin = $this->createMock(EmailOrigin::class);
        $this->emailOriginHelper->expects(self::once())
            ->method('getEmailOrigin')
            ->with($this->from->toString(), $organization)
            ->willReturn($emailOrigin);

        $emailModel = $this->factory->createEmailModel($this->from, $recipient1, $templateName, $templateParams);

        self::assertEquals($this->from->toString(), $emailModel->getFrom());
        self::assertEquals($organization, $emailModel->getOrganization());
        self::assertEquals($emailOrigin, $emailModel->getOrigin());
        self::assertEquals([$recipient1->getEmail()], $emailModel->getTo());
        self::assertEquals($emailTemplateModel->getSubject(), $emailModel->getSubject());
        self::assertEquals($emailTemplateModel->getContent(), $emailModel->getBody());
        self::assertEquals('html', $emailModel->getType());
    }

    public function testCreateEmailModelWithAttachments(): void
    {
        $recipient = (new User())->setEmail('recipient1@example.com');
        $templateName = 'sample_template';
        $templateParams = ['sample_key' => 'sample_value'];
        $templateContext = ['localization' => new Localization()];

        $this->emailTemplateContextProvider->expects(self::once())
            ->method('getTemplateContext')
            ->with($this->from, [$recipient], $templateName, $templateParams)
            ->willReturn($templateContext);

        $attachment1 = new EmailTemplateAttachmentModel();
        $attachment2 = new EmailTemplateAttachmentModel();

        $emailTemplateModel = (new EmailTemplateModel($templateName, 'sample_content'))
            ->setSubject('sample_subject')
            ->addAttachment($attachment1)
            ->addAttachment($attachment2);

        $this->renderedEmailTemplateProvider->expects(self::once())
            ->method('findAndRenderEmailTemplate')
            ->with($templateName, $templateParams, $templateContext)
            ->willReturn($emailTemplateModel);

        $emailOrigin = $this->createMock(EmailOrigin::class);
        $this->emailOriginHelper->expects(self::once())
            ->method('getEmailOrigin')
            ->with($this->from->toString(), null)
            ->willReturn($emailOrigin);

        $emailAttachmentModel1 = new EmailAttachmentModel();
        $emailAttachmentModel2 = new EmailAttachmentModel();

        $this->emailAttachmentModelFactory
            ->expects(self::exactly(2))
            ->method('createEmailAttachmentModels')
            ->withConsecutive([$attachment1, $templateParams], [$attachment2, $templateParams])
            ->willReturnOnConsecutiveCalls([0 => $emailAttachmentModel1], [0 => $emailAttachmentModel2]);

        $emailAttachmentEntity1 = new EmailAttachment();
        $emailAttachmentEntity2 = new EmailAttachment();

        $this->emailAttachmentEntityFactory
            ->expects(self::exactly(2))
            ->method('createEmailAttachmentEntities')
            ->withConsecutive([$attachment1, $templateParams], [$attachment2, $templateParams])
            ->willReturnOnConsecutiveCalls([0 => $emailAttachmentEntity1], [0 => $emailAttachmentEntity2]);

        $emailModel = $this->factory->createEmailModel($this->from, $recipient, $templateName, $templateParams);

        self::assertEquals($this->from->toString(), $emailModel->getFrom());
        self::assertEquals($emailOrigin, $emailModel->getOrigin());
        self::assertEquals([$recipient->getEmail()], $emailModel->getTo());
        self::assertEquals($emailTemplateModel->getSubject(), $emailModel->getSubject());
        self::assertEquals($emailTemplateModel->getContent(), $emailModel->getBody());
        self::assertEquals('html', $emailModel->getType());

        self::assertCount(2, $emailModel->getAttachments());
        self::assertSame($emailAttachmentEntity1, $emailModel->getAttachments()->first()->getEmailAttachment());
        self::assertSame($emailAttachmentEntity2, $emailModel->getAttachments()->last()->getEmailAttachment());
    }

    public function testCreateEmailModelWithMultipleFileItemAttachments(): void
    {
        $recipient = (new User())->setEmail('recipient1@example.com');
        $templateName = 'sample_template';
        $templateParams = ['sample_key' => 'sample_value'];
        $templateContext = ['localization' => new Localization()];

        $this->emailTemplateContextProvider->expects(self::once())
            ->method('getTemplateContext')
            ->with($this->from, [$recipient], $templateName, $templateParams)
            ->willReturn($templateContext);

        $attachment1 = new EmailTemplateAttachmentModel();

        $emailTemplateModel = (new EmailTemplateModel($templateName, 'sample_content'))
            ->setSubject('sample_subject')
            ->addAttachment($attachment1);

        $this->renderedEmailTemplateProvider->expects(self::once())
            ->method('findAndRenderEmailTemplate')
            ->with($templateName, $templateParams, $templateContext)
            ->willReturn($emailTemplateModel);

        $emailOrigin = $this->createMock(EmailOrigin::class);
        $this->emailOriginHelper->expects(self::once())
            ->method('getEmailOrigin')
            ->with($this->from->toString(), null)
            ->willReturn($emailOrigin);

        $emailAttachmentModel1 = new EmailAttachmentModel();
        $emailAttachmentModel2 = new EmailAttachmentModel();

        $this->emailAttachmentModelFactory
            ->expects(self::once())
            ->method('createEmailAttachmentModels')
            ->with($attachment1, $templateParams)
            ->willReturn([0 => $emailAttachmentModel1, 1 => $emailAttachmentModel2]);

        $emailAttachmentEntity1 = new EmailAttachment();
        $emailAttachmentEntity2 = new EmailAttachment();

        $this->emailAttachmentEntityFactory
            ->expects(self::once())
            ->method('createEmailAttachmentEntities')
            ->with($attachment1, $templateParams)
            ->willReturn([0 => $emailAttachmentEntity1, 1 => $emailAttachmentEntity2]);

        $emailModel = $this->factory->createEmailModel($this->from, $recipient, $templateName, $templateParams);

        self::assertEquals($this->from->toString(), $emailModel->getFrom());
        self::assertEquals($emailOrigin, $emailModel->getOrigin());
        self::assertEquals([$recipient->getEmail()], $emailModel->getTo());
        self::assertEquals($emailTemplateModel->getSubject(), $emailModel->getSubject());
        self::assertEquals($emailTemplateModel->getContent(), $emailModel->getBody());
        self::assertEquals('html', $emailModel->getType());

        self::assertCount(2, $emailModel->getAttachments());
        self::assertSame($emailAttachmentEntity1, $emailModel->getAttachments()->first()->getEmailAttachment());
        self::assertSame($emailAttachmentEntity2, $emailModel->getAttachments()->last()->getEmailAttachment());
    }

    public function testCreateEmailModelWithInvalidAttachments(): void
    {
        $recipient = (new User())->setEmail('recipient1@example.com');
        $templateName = 'sample_template';
        $templateParams = ['sample_key' => 'sample_value'];
        $templateContext = ['localization' => new Localization()];

        $this->emailTemplateContextProvider->expects(self::once())
            ->method('getTemplateContext')
            ->with($this->from, [$recipient], $templateName, $templateParams)
            ->willReturn($templateContext);

        $attachment1 = new EmailTemplateAttachmentModel();
        $attachment2 = new EmailTemplateAttachmentModel();
        $attachment3 = new EmailTemplateAttachmentModel();

        $emailTemplateModel = (new EmailTemplateModel($templateName, 'sample_content'))
            ->setSubject('sample_subject')
            ->addAttachment($attachment1)
            ->addAttachment($attachment2)
            ->addAttachment($attachment3);

        $this->renderedEmailTemplateProvider->expects(self::once())
            ->method('findAndRenderEmailTemplate')
            ->with($templateName, $templateParams, $templateContext)
            ->willReturn($emailTemplateModel);

        $emailOrigin = $this->createMock(EmailOrigin::class);
        $this->emailOriginHelper->expects(self::once())
            ->method('getEmailOrigin')
            ->with($this->from->toString(), null)
            ->willReturn($emailOrigin);

        // Mock factory returns: empty array, valid model array, empty array
        $emailAttachmentModel = new EmailAttachmentModel();
        $emailAttachmentEntity = new EmailAttachment();

        $this->emailAttachmentModelFactory
            ->expects(self::exactly(3))
            ->method('createEmailAttachmentModels')
            ->withConsecutive(
                [$attachment1, $templateParams],
                [$attachment2, $templateParams],
                [$attachment3, $templateParams]
            )
            ->willReturnOnConsecutiveCalls([], [0 => $emailAttachmentModel], []);

        $this->emailAttachmentEntityFactory
            ->expects(self::once()) // Only called for the valid attachment model
            ->method('createEmailAttachmentEntities')
            ->with($attachment2, $templateParams)
            ->willReturn([0 => $emailAttachmentEntity]);

        $emailModel = $this->factory->createEmailModel($this->from, $recipient, $templateName, $templateParams);

        // Only the valid attachment should be included
        self::assertCount(1, $emailModel->getAttachments());
        self::assertSame($emailAttachmentEntity, $emailModel->getAttachments()->first()->getEmailAttachment());
    }

    public function testCreateEmailModelWithNullAttachmentEntity(): void
    {
        $recipient = (new User())->setEmail('recipient1@example.com');
        $templateName = 'sample_template';
        $templateParams = ['sample_key' => 'sample_value'];
        $templateContext = ['localization' => new Localization()];

        $this->emailTemplateContextProvider->expects(self::once())
            ->method('getTemplateContext')
            ->with($this->from, [$recipient], $templateName, $templateParams)
            ->willReturn($templateContext);

        $attachment1 = new EmailTemplateAttachmentModel();

        $emailTemplateModel = (new EmailTemplateModel($templateName, 'sample_content'))
            ->setSubject('sample_subject')
            ->addAttachment($attachment1);

        $this->renderedEmailTemplateProvider->expects(self::once())
            ->method('findAndRenderEmailTemplate')
            ->with($templateName, $templateParams, $templateContext)
            ->willReturn($emailTemplateModel);

        $emailOrigin = $this->createMock(EmailOrigin::class);
        $this->emailOriginHelper->expects(self::once())
            ->method('getEmailOrigin')
            ->with($this->from->toString(), null)
            ->willReturn($emailOrigin);

        // Mock valid attachment model but empty entity array
        $emailAttachmentModel = new EmailAttachmentModel();

        $this->emailAttachmentModelFactory
            ->expects(self::once())
            ->method('createEmailAttachmentModels')
            ->with($attachment1, $templateParams)
            ->willReturn([0 => $emailAttachmentModel]);

        $this->emailAttachmentEntityFactory
            ->expects(self::once())
            ->method('createEmailAttachmentEntities')
            ->with($attachment1, $templateParams)
            ->willReturn([]); // Entity creation fails (empty array)

        $emailModel = $this->factory->createEmailModel($this->from, $recipient, $templateName, $templateParams);

        // No attachments should be included since entity creation failed
        self::assertCount(0, $emailModel->getAttachments());
    }

    public function testCreateEmailModelWithMismatchedAttachmentIndexes(): void
    {
        $recipient = (new User())->setEmail('recipient1@example.com');
        $templateName = 'sample_template';
        $templateParams = ['sample_key' => 'sample_value'];
        $templateContext = ['localization' => new Localization()];

        $this->emailTemplateContextProvider->expects(self::once())
            ->method('getTemplateContext')
            ->with($this->from, [$recipient], $templateName, $templateParams)
            ->willReturn($templateContext);

        $attachment1 = new EmailTemplateAttachmentModel();

        $emailTemplateModel = (new EmailTemplateModel($templateName, 'sample_content'))
            ->setSubject('sample_subject')
            ->addAttachment($attachment1);

        $this->renderedEmailTemplateProvider->expects(self::once())
            ->method('findAndRenderEmailTemplate')
            ->with($templateName, $templateParams, $templateContext)
            ->willReturn($emailTemplateModel);

        $emailOrigin = $this->createMock(EmailOrigin::class);
        $this->emailOriginHelper->expects(self::once())
            ->method('getEmailOrigin')
            ->with($this->from->toString(), null)
            ->willReturn($emailOrigin);

        $emailAttachmentModel1 = new EmailAttachmentModel();
        $emailAttachmentModel2 = new EmailAttachmentModel();

        $emailAttachmentEntity2 = new EmailAttachment();

        $this->emailAttachmentModelFactory
            ->expects(self::once())
            ->method('createEmailAttachmentModels')
            ->with($attachment1, $templateParams)
            ->willReturn([0 => $emailAttachmentModel1, 2 => $emailAttachmentModel2]); // Index 0 and 2

        $this->emailAttachmentEntityFactory
            ->expects(self::once())
            ->method('createEmailAttachmentEntities')
            ->with($attachment1, $templateParams)
            ->willReturn([2 => $emailAttachmentEntity2]); // Only index 2

        $emailModel = $this->factory->createEmailModel($this->from, $recipient, $templateName, $templateParams);

        // Only the attachment with matching index should be included
        self::assertCount(1, $emailModel->getAttachments());
        self::assertSame($emailAttachmentEntity2, $emailModel->getAttachments()->first()->getEmailAttachment());
    }
}
