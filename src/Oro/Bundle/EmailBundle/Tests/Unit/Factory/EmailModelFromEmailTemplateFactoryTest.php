<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Factory;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Factory\EmailModelFromEmailTemplateFactory;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Form\Model\Factory as EmailModelFactory;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
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

class EmailModelFromEmailTemplateFactoryTest extends TestCase
{
    private EmailTemplateContextProvider $emailTemplateContextProvider;

    private RenderedEmailTemplateProvider|MockObject $renderedEmailTemplateProvider;

    private EmailOriginHelper|MockObject $emailOriginHelper;

    private EntityOwnerAccessor|MockObject $entityOwnerAccessor;

    private EmailModelFromEmailTemplateFactory $factory;

    private From $from;

    #[\Override]
    protected function setUp(): void
    {
        $this->emailTemplateContextProvider = $this->createMock(EmailTemplateContextProvider::class);
        $this->renderedEmailTemplateProvider = $this->createMock(RenderedEmailTemplateProvider::class);
        $emailModelFactory = $this->createMock(EmailModelFactory::class);
        $this->emailOriginHelper = $this->createMock(EmailOriginHelper::class);
        $this->entityOwnerAccessor = $this->createMock(EntityOwnerAccessor::class);

        $this->factory = new EmailModelFromEmailTemplateFactory(
            $this->emailTemplateContextProvider,
            $this->renderedEmailTemplateProvider,
            $emailModelFactory,
            $this->emailOriginHelper,
            $this->entityOwnerAccessor
        );

        $emailModelFactory
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

        $this->emailTemplateContextProvider
            ->expects(self::once())
            ->method('getTemplateContext')
            ->with($this->from, [$recipient], $templateName, $templateParams)
            ->willReturn($templateContext);

        $emailTemplateModel = (new EmailTemplateModel($templateName))
            ->setSubject('sample_subject')
            ->setContent('sample_content')
            ->setType(EmailTemplateInterface::TYPE_TEXT);

        $this->renderedEmailTemplateProvider
            ->expects(self::once())
            ->method('findAndRenderEmailTemplate')
            ->with($templateName, $templateParams, $templateContext)
            ->willReturn($emailTemplateModel);

        $emailOrigin = $this->createMock(EmailOrigin::class);
        $this->emailOriginHelper
            ->expects(self::once())
            ->method('getEmailOrigin')
            ->with($this->from->toString(), null)
            ->willReturn($emailOrigin);

        $emailModel = (new EmailModel())
            ->setFrom($this->from->toString())
            ->setOrigin($emailOrigin)
            ->setTo([$recipient->getEmail()])
            ->setSubject($emailTemplateModel->getSubject())
            ->setBody($emailTemplateModel->getContent())
            ->setType('text');

        self::assertEquals(
            $emailModel,
            $this->factory->createEmailModel($this->from, $recipient, $templateName, $templateParams)
        );
    }

    public function testCreateEmailModelWithSingleRecipient(): void
    {
        $recipient = (new User())->setEmail('recipient1@example.com');
        $templateName = 'sample_template';
        $templateParams = ['sample_key' => 'sample_value'];
        $templateContext = ['localization' => new Localization()];

        $this->emailTemplateContextProvider
            ->expects(self::once())
            ->method('getTemplateContext')
            ->with($this->from, [$recipient], $templateName, $templateParams)
            ->willReturn($templateContext);

        $emailTemplateModel = (new EmailTemplateModel($templateName, 'sample_content'))
            ->setSubject('sample_subject');

        $this->renderedEmailTemplateProvider
            ->expects(self::once())
            ->method('findAndRenderEmailTemplate')
            ->with($templateName, $templateParams, $templateContext)
            ->willReturn($emailTemplateModel);

        $emailOrigin = $this->createMock(EmailOrigin::class);
        $this->emailOriginHelper
            ->expects(self::once())
            ->method('getEmailOrigin')
            ->with($this->from->toString(), null)
            ->willReturn($emailOrigin);

        $emailModel = (new EmailModel())
            ->setFrom($this->from->toString())
            ->setOrigin($emailOrigin)
            ->setTo([$recipient->getEmail()])
            ->setSubject($emailTemplateModel->getSubject())
            ->setBody($emailTemplateModel->getContent())
            ->setType('html');

        self::assertEquals(
            $emailModel,
            $this->factory->createEmailModel($this->from, $recipient, $templateName, $templateParams)
        );
    }

    public function testCreateEmailModelWithMultipleRecipients(): void
    {
        $recipient1 = (new User())->setEmail('recipient1@example.com');
        $recipient2 = (new User())->setEmail('recipient2@example.com');
        $templateName = 'sample_template';
        $templateParams = ['sample_key' => 'sample_value'];
        $templateContext = ['localization' => new Localization()];

        $this->emailTemplateContextProvider
            ->expects(self::once())
            ->method('getTemplateContext')
            ->with($this->from, [$recipient1, $recipient2], $templateName, $templateParams)
            ->willReturn($templateContext);

        $emailTemplateModel = (new EmailTemplateModel($templateName, 'sample_content'))
            ->setSubject('sample_subject');

        $this->renderedEmailTemplateProvider
            ->expects(self::once())
            ->method('findAndRenderEmailTemplate')
            ->with($templateName, $templateParams, $templateContext)
            ->willReturn($emailTemplateModel);

        $emailOrigin = $this->createMock(EmailOrigin::class);
        $this->emailOriginHelper
            ->expects(self::once())
            ->method('getEmailOrigin')
            ->with($this->from->toString(), null)
            ->willReturn($emailOrigin);

        $emailModel = (new EmailModel())
            ->setFrom($this->from->toString())
            ->setOrigin($emailOrigin)
            ->setTo([$recipient1->getEmail(), $recipient2->getEmail()])
            ->setSubject($emailTemplateModel->getSubject())
            ->setBody($emailTemplateModel->getContent())
            ->setType('html');

        self::assertEquals(
            $emailModel,
            $this->factory->createEmailModel($this->from, [$recipient1, $recipient2], $templateName, $templateParams)
        );
    }

    public function testCreateEmailModelWithEmailTemplateCriteriaAndNoEntity(): void
    {
        $recipient1 = (new User())->setEmail('recipient1@example.com');
        $templateName = 'sample_template';
        $templateParams = ['sample_key' => 'sample_value'];
        $emailTemplateCriteria = new EmailTemplateCriteria($templateName);
        $templateContext = ['localization' => new Localization()];

        $this->emailTemplateContextProvider
            ->expects(self::once())
            ->method('getTemplateContext')
            ->with($this->from, [$recipient1], $emailTemplateCriteria, $templateParams)
            ->willReturn($templateContext);

        $emailTemplateModel = (new EmailTemplateModel($templateName, 'sample_content'))
            ->setSubject('sample_subject');

        $this->renderedEmailTemplateProvider
            ->expects(self::once())
            ->method('findAndRenderEmailTemplate')
            ->with($emailTemplateCriteria, $templateParams)
            ->willReturn($emailTemplateModel);

        $emailOrigin = $this->createMock(EmailOrigin::class);
        $this->emailOriginHelper
            ->expects(self::once())
            ->method('getEmailOrigin')
            ->with($this->from->toString(), null)
            ->willReturn($emailOrigin);

        $emailModel = (new EmailModel())
            ->setFrom($this->from->toString())
            ->setOrigin($emailOrigin)
            ->setTo([$recipient1->getEmail()])
            ->setSubject($emailTemplateModel->getSubject())
            ->setBody($emailTemplateModel->getContent())
            ->setType('html');

        self::assertEquals(
            $emailModel,
            $this->factory->createEmailModel($this->from, $recipient1, $emailTemplateCriteria, $templateParams)
        );
    }

    public function testCreateEmailModelWithEmailTemplateCriteriaAndEntity(): void
    {
        $recipient1 = (new User())->setEmail('recipient1@example.com');
        $templateName = 'sample_template';
        $templateParams = ['entity' => new \stdClass(), 'sample_key' => 'sample_value'];
        $emailTemplateCriteria = new EmailTemplateCriteria($templateName, 'Acme\\Bundle\\Entity\\SampleEntity');
        $templateContext = ['localization' => new Localization()];

        $this->emailTemplateContextProvider
            ->expects(self::once())
            ->method('getTemplateContext')
            ->with($this->from, [$recipient1], $emailTemplateCriteria, $templateParams)
            ->willReturn($templateContext);

        $emailTemplateModel = (new EmailTemplateModel($templateName, 'sample_content'))
            ->setSubject('sample_subject');

        $this->renderedEmailTemplateProvider
            ->expects(self::once())
            ->method('findAndRenderEmailTemplate')
            ->with($emailTemplateCriteria, $templateParams, $templateContext)
            ->willReturn($emailTemplateModel);

        $organization = $this->createMock(Organization::class);
        $this->entityOwnerAccessor
            ->expects(self::once())
            ->method('getOrganization')
            ->with($templateParams['entity'])
            ->willReturn($organization);

        $emailOrigin = $this->createMock(EmailOrigin::class);
        $this->emailOriginHelper
            ->expects(self::once())
            ->method('getEmailOrigin')
            ->with($this->from->toString(), $organization)
            ->willReturn($emailOrigin);

        $emailModel = (new EmailModel())
            ->setFrom($this->from->toString())
            ->setOrganization($organization)
            ->setOrigin($emailOrigin)
            ->setTo([$recipient1->getEmail()])
            ->setSubject($emailTemplateModel->getSubject())
            ->setBody($emailTemplateModel->getContent())
            ->setType('html');

        self::assertEquals(
            $emailModel,
            $this->factory->createEmailModel($this->from, $recipient1, $emailTemplateCriteria, $templateParams)
        );
    }

    public function testCreateEmailModelWithTemplateNameAndEntity(): void
    {
        $recipient1 = (new User())->setEmail('recipient1@example.com');
        $templateName = 'sample_template';
        $templateParams = ['entity' => new \stdClass(), 'sample_key' => 'sample_value'];
        $emailTemplateCriteria = new EmailTemplateCriteria($templateName);
        $templateContext = ['localization' => new Localization()];

        $this->emailTemplateContextProvider
            ->expects(self::once())
            ->method('getTemplateContext')
            ->with($this->from, [$recipient1], $emailTemplateCriteria, $templateParams)
            ->willReturn($templateContext);

        $emailTemplateModel = (new EmailTemplateModel($templateName, 'sample_content'))
            ->setSubject('sample_subject');

        $this->renderedEmailTemplateProvider
            ->expects(self::once())
            ->method('findAndRenderEmailTemplate')
            ->with($emailTemplateCriteria, $templateParams, $templateContext)
            ->willReturn($emailTemplateModel);

        $organization = $this->createMock(Organization::class);
        $this->entityOwnerAccessor
            ->expects(self::once())
            ->method('getOrganization')
            ->with($templateParams['entity'])
            ->willReturn($organization);

        $emailOrigin = $this->createMock(EmailOrigin::class);
        $this->emailOriginHelper
            ->expects(self::once())
            ->method('getEmailOrigin')
            ->with($this->from->toString(), $organization)
            ->willReturn($emailOrigin);

        $emailModel = (new EmailModel())
            ->setFrom($this->from->toString())
            ->setOrganization($organization)
            ->setOrigin($emailOrigin)
            ->setTo([$recipient1->getEmail()])
            ->setSubject($emailTemplateModel->getSubject())
            ->setBody($emailTemplateModel->getContent())
            ->setType('html');

        self::assertEquals(
            $emailModel,
            $this->factory->createEmailModel($this->from, $recipient1, $emailTemplateCriteria, $templateParams)
        );
    }
}
