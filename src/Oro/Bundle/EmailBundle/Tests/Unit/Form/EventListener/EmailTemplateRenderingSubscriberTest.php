<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\EmailBundle\Builder\Helper\EmailModelBuilderHelper;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate as EmailTemplateEntity;
use Oro\Bundle\EmailBundle\Form\EventListener\EmailTemplateRenderingSubscriber;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Provider\EmailTemplateContextProvider;
use Oro\Bundle\EmailBundle\Provider\TranslatedEmailTemplateProvider;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\NotificationBundle\Model\EmailAddressWithContext;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\FormInterface;

class EmailTemplateRenderingSubscriberTest extends TestCase
{
    private EmailModelBuilderHelper|MockObject $emailModelBuilderHelper;

    private TranslatedEmailTemplateProvider|MockObject $translatedEmailTemplateProvider;

    private EmailTemplateContextProvider|MockObject $emailTemplateContextProvider;

    private EmailRenderer|MockObject $emailRenderer;

    private EmailTemplateRenderingSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->emailModelBuilderHelper = $this->createMock(EmailModelBuilderHelper::class);
        $this->translatedEmailTemplateProvider = $this->createMock(TranslatedEmailTemplateProvider::class);
        $this->emailTemplateContextProvider = $this->createMock(EmailTemplateContextProvider::class);
        $this->emailRenderer = $this->createMock(EmailRenderer::class);

        $this->subscriber = new EmailTemplateRenderingSubscriber(
            $this->emailModelBuilderHelper,
            $this->translatedEmailTemplateProvider,
            $this->emailTemplateContextProvider,
            $this->emailRenderer
        );
    }

    public function testShouldSkipWhenNotEmailModel(): void
    {
        $event = new PreSetDataEvent($this->createMock(FormInterface::class), new \stdClass());

        $this->emailTemplateContextProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->translatedEmailTemplateProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->emailRenderer
            ->expects(self::never())
            ->method(self::anything());

        $this->subscriber->onPreSetData($event);
    }

    public function testShouldSkipWhenNoEmailTemplate(): void
    {
        $event = new PreSetDataEvent($this->createMock(FormInterface::class), new EmailModel());

        $this->emailTemplateContextProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->translatedEmailTemplateProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->emailRenderer
            ->expects(self::never())
            ->method(self::anything());

        $this->subscriber->onPreSetData($event);
    }

    public function testShouldSkipWhenHasSubjectAndBody(): void
    {
        $emailTemplate = new EmailTemplateEntity();
        $emailModel = (new EmailModel())
            ->setTemplate($emailTemplate)
            ->setSubject('sample subject')
            ->setBody('sample body');
        $event = new PreSetDataEvent($this->createMock(FormInterface::class), $emailModel);

        $this->emailTemplateContextProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->translatedEmailTemplateProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->emailRenderer
            ->expects(self::never())
            ->method(self::anything());

        $this->subscriber->onPreSetData($event);
    }

    public function testShouldSkipWhenNoEntityClass(): void
    {
        $emailTemplate = new EmailTemplateEntity();
        $emailModel = (new EmailModel())
            ->setTemplate($emailTemplate);
        $event = new PreSetDataEvent($this->createMock(FormInterface::class), $emailModel);

        $this->emailTemplateContextProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->translatedEmailTemplateProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->emailRenderer
            ->expects(self::never())
            ->method(self::anything());

        $this->subscriber->onPreSetData($event);
    }

    public function testShouldSkipWhenNoEntityId(): void
    {
        $emailTemplate = new EmailTemplateEntity();
        $emailModel = (new EmailModel())
            ->setTemplate($emailTemplate)
            ->setEntityClass(User::class);
        $event = new PreSetDataEvent($this->createMock(FormInterface::class), $emailModel);

        $this->emailTemplateContextProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->translatedEmailTemplateProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->emailRenderer
            ->expects(self::never())
            ->method(self::anything());

        $this->subscriber->onPreSetData($event);
    }

    public function testShouldFillSubjectAndBody(): void
    {
        $emailTemplate = new EmailTemplateEntity('sample_template');
        $entityClass = User::class;
        $entityId = 42;
        $from = 'no-reply@example.com';
        $recipient = 'user1@example.com';
        $emailModel = (new EmailModel())
            ->setTemplate($emailTemplate)
            ->setEntityClass($entityClass)
            ->setEntityId($entityId)
            ->setFrom($from)
            ->setTo([$recipient]);
        $event = new PreSetDataEvent($this->createMock(FormInterface::class), $emailModel);

        $targetEntity = new UserStub($entityId);
        $this->emailModelBuilderHelper
            ->expects(self::once())
            ->method('getTargetEntity')
            ->with($entityClass, $entityId)
            ->willReturn($targetEntity);

        $emailTemplateCriteria = new EmailTemplateCriteria($emailTemplate->getName(), $emailTemplate->getEntityName());

        $templateContext = ['localization' => new Localization()];
        $templateParams = ['entity' => $targetEntity];
        $this->emailTemplateContextProvider
            ->expects(self::once())
            ->method('getTemplateContext')
            ->with(
                From::emailAddress($emailModel->getFrom()),
                [new EmailAddressWithContext(current($emailModel->getTo()), $targetEntity)],
                $emailTemplateCriteria,
                $templateParams
            )
            ->willReturn($templateContext);

        $translatedEmailTemplate = (new EmailTemplateModel($emailTemplate->getName()))
            ->setSubject('translated subject')
            ->setContent('translated content');
        $this->translatedEmailTemplateProvider
            ->expects(self::once())
            ->method('getTranslatedEmailTemplate')
            ->with($emailTemplate, $templateContext['localization'])
            ->willReturn($translatedEmailTemplate);

        $renderedEmailTemplate = (new EmailTemplateModel($emailTemplate->getName()))
            ->setSubject('rendered subject')
            ->setContent('rendered content');

        $this->emailRenderer
            ->expects(self::once())
            ->method('renderEmailTemplate')
            ->with($translatedEmailTemplate, $templateParams, $templateContext)
            ->willReturn($renderedEmailTemplate);

        $this->subscriber->onPreSetData($event);

        self::assertEquals($renderedEmailTemplate->getSubject(), $emailModel->getSubject());
        self::assertEquals($renderedEmailTemplate->getContent(), $emailModel->getBody());
    }

    public function testShouldFillSubjectAndBodyWhenNoTemplateContext(): void
    {
        $emailTemplate = new EmailTemplateEntity('sample_template');
        $entityClass = User::class;
        $entityId = 42;
        $from = 'no-reply@example.com';
        $recipient = 'user1@example.com';
        $emailModel = (new EmailModel())
            ->setTemplate($emailTemplate)
            ->setEntityClass($entityClass)
            ->setEntityId($entityId)
            ->setFrom($from)
            ->setTo([$recipient]);
        $event = new PreSetDataEvent($this->createMock(FormInterface::class), $emailModel);

        $targetEntity = new UserStub($entityId);
        $this->emailModelBuilderHelper
            ->expects(self::once())
            ->method('getTargetEntity')
            ->with($entityClass, $entityId)
            ->willReturn($targetEntity);

        $emailTemplateCriteria = new EmailTemplateCriteria($emailTemplate->getName(), $emailTemplate->getEntityName());

        $templateParams = ['entity' => $targetEntity];
        $this->emailTemplateContextProvider
            ->expects(self::once())
            ->method('getTemplateContext')
            ->with(
                From::emailAddress($emailModel->getFrom()),
                [new EmailAddressWithContext(current($emailModel->getTo()), $targetEntity)],
                $emailTemplateCriteria,
                $templateParams
            )
            ->willReturn([]);

        $translatedEmailTemplate = (new EmailTemplateModel($emailTemplate->getName()))
            ->setSubject('translated subject')
            ->setContent('translated content');
        $this->translatedEmailTemplateProvider
            ->expects(self::once())
            ->method('getTranslatedEmailTemplate')
            ->with($emailTemplate, null)
            ->willReturn($translatedEmailTemplate);

        $renderedEmailTemplate = (new EmailTemplateModel($emailTemplate->getName()))
            ->setSubject('rendered subject')
            ->setContent('rendered content');

        $this->emailRenderer
            ->expects(self::once())
            ->method('renderEmailTemplate')
            ->with($translatedEmailTemplate, $templateParams, [])
            ->willReturn($renderedEmailTemplate);

        $this->subscriber->onPreSetData($event);

        self::assertEquals($renderedEmailTemplate->getSubject(), $emailModel->getSubject());
        self::assertEquals($renderedEmailTemplate->getContent(), $emailModel->getBody());
    }

    public function testShouldFillOnlySubjectWhenBodyAlreadySet(): void
    {
        $emailTemplate = new EmailTemplateEntity('sample_template');
        $entityClass = User::class;
        $entityId = 42;
        $from = 'no-reply@example.com';
        $recipient = 'user1@example.com';
        $emailModel = (new EmailModel())
            ->setTemplate($emailTemplate)
            ->setEntityClass($entityClass)
            ->setEntityId($entityId)
            ->setFrom($from)
            ->setTo([$recipient])
            ->setBody('already set body');
        $event = new PreSetDataEvent($this->createMock(FormInterface::class), $emailModel);

        $targetEntity = new UserStub($entityId);
        $this->emailModelBuilderHelper
            ->expects(self::once())
            ->method('getTargetEntity')
            ->with($entityClass, $entityId)
            ->willReturn($targetEntity);

        $emailTemplateCriteria = new EmailTemplateCriteria($emailTemplate->getName(), $emailTemplate->getEntityName());

        $templateParams = ['entity' => $targetEntity];
        $this->emailTemplateContextProvider
            ->expects(self::once())
            ->method('getTemplateContext')
            ->with(
                From::emailAddress($emailModel->getFrom()),
                [new EmailAddressWithContext(current($emailModel->getTo()), $targetEntity)],
                $emailTemplateCriteria,
                $templateParams
            )
            ->willReturn([]);

        $translatedEmailTemplate = (new EmailTemplateModel($emailTemplate->getName()))
            ->setSubject('translated subject')
            ->setContent('translated content');
        $this->translatedEmailTemplateProvider
            ->expects(self::once())
            ->method('getTranslatedEmailTemplate')
            ->with($emailTemplate, null)
            ->willReturn($translatedEmailTemplate);

        $renderedEmailTemplate = (new EmailTemplateModel($emailTemplate->getName()))
            ->setSubject('rendered subject')
            ->setContent('rendered content');

        $this->emailRenderer
            ->expects(self::once())
            ->method('renderEmailTemplate')
            ->with($translatedEmailTemplate, $templateParams, [])
            ->willReturn($renderedEmailTemplate);

        $this->subscriber->onPreSetData($event);

        self::assertEquals($renderedEmailTemplate->getSubject(), $emailModel->getSubject());
        self::assertEquals('already set body', $emailModel->getBody());
    }

    public function testShouldFillOnlyBodyWhenSubjectAlreadySet(): void
    {
        $emailTemplate = new EmailTemplateEntity('sample_template');
        $entityClass = User::class;
        $entityId = 42;
        $from = 'no-reply@example.com';
        $recipient = 'user1@example.com';
        $emailModel = (new EmailModel())
            ->setTemplate($emailTemplate)
            ->setEntityClass($entityClass)
            ->setEntityId($entityId)
            ->setFrom($from)
            ->setTo([$recipient])
            ->setSubject('already set subject');
        $event = new PreSetDataEvent($this->createMock(FormInterface::class), $emailModel);

        $targetEntity = new UserStub($entityId);
        $this->emailModelBuilderHelper
            ->expects(self::once())
            ->method('getTargetEntity')
            ->with($entityClass, $entityId)
            ->willReturn($targetEntity);

        $emailTemplateCriteria = new EmailTemplateCriteria($emailTemplate->getName(), $emailTemplate->getEntityName());

        $templateParams = ['entity' => $targetEntity];
        $this->emailTemplateContextProvider
            ->expects(self::once())
            ->method('getTemplateContext')
            ->with(
                From::emailAddress($emailModel->getFrom()),
                [new EmailAddressWithContext(current($emailModel->getTo()), $targetEntity)],
                $emailTemplateCriteria,
                $templateParams
            )
            ->willReturn([]);

        $translatedEmailTemplate = (new EmailTemplateModel($emailTemplate->getName()))
            ->setSubject('translated subject')
            ->setContent('translated content');
        $this->translatedEmailTemplateProvider
            ->expects(self::once())
            ->method('getTranslatedEmailTemplate')
            ->with($emailTemplate, null)
            ->willReturn($translatedEmailTemplate);

        $renderedEmailTemplate = (new EmailTemplateModel($emailTemplate->getName()))
            ->setSubject('rendered subject')
            ->setContent('rendered content');

        $this->emailRenderer
            ->expects(self::once())
            ->method('renderEmailTemplate')
            ->with($translatedEmailTemplate, $templateParams, [])
            ->willReturn($renderedEmailTemplate);

        $this->subscriber->onPreSetData($event);

        self::assertEquals('already set subject', $emailModel->getSubject());
        self::assertEquals($renderedEmailTemplate->getContent(), $emailModel->getBody());
    }
}
