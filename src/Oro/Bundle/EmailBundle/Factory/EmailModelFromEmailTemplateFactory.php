<?php

namespace Oro\Bundle\EmailBundle\Factory;

use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment as EmailAttachmentModel;
use Oro\Bundle\EmailBundle\Form\Model\Factory as EmailModelFactory;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Provider\EmailTemplateContextProvider;
use Oro\Bundle\EmailBundle\Provider\RenderedEmailTemplateProvider;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;

/**
 * Creates an {@see EmailModel} from an email template.
 */
class EmailModelFromEmailTemplateFactory
{
    private EmailTemplateContextProvider $emailTemplateContextProvider;

    private RenderedEmailTemplateProvider $renderedEmailTemplateProvider;

    private EmailModelFactory $emailModelFactory;

    private EmailAttachmentModelFromEmailTemplateAttachmentFactory $emailAttachmentModelFactory;

    private EmailAttachmentEntityFromEmailTemplateAttachmentFactory $emailAttachmentEntityFactory;

    private EmailOriginHelper $emailOriginHelper;

    private EntityOwnerAccessor $entityOwnerAccessor;

    public function __construct(
        EmailTemplateContextProvider $emailTemplateContextProvider,
        RenderedEmailTemplateProvider $renderedEmailTemplateProvider,
        EmailModelFactory $emailModelFactory,
        EmailAttachmentModelFromEmailTemplateAttachmentFactory $emailAttachmentModelFactory,
        EmailAttachmentEntityFromEmailTemplateAttachmentFactory $emailAttachmentEntityFactory,
        EmailOriginHelper $emailOriginHelper,
        EntityOwnerAccessor $entityOwnerAccessor
    ) {
        $this->emailTemplateContextProvider = $emailTemplateContextProvider;
        $this->renderedEmailTemplateProvider = $renderedEmailTemplateProvider;
        $this->emailModelFactory = $emailModelFactory;
        $this->emailAttachmentModelFactory = $emailAttachmentModelFactory;
        $this->emailAttachmentEntityFactory = $emailAttachmentEntityFactory;
        $this->emailOriginHelper = $emailOriginHelper;
        $this->entityOwnerAccessor = $entityOwnerAccessor;
    }

    /**
     * @param From $from
     * @param EmailHolderInterface|array<EmailHolderInterface> $recipients
     * @param EmailTemplateCriteria|string $templateName
     * @param array $templateParams
     * @param array $templateContext Email template context. Example:
     *  [
     *      'localization' => Localization|int $localization,
     *      // ... other context parameters supported by the existing candidates names
     *      // providers {@see EmailTemplateCandidatesProviderInterface}
     *  ]
     *
     * @return EmailModel Ready to be used in {@see EmailModelSender}
     */
    public function createEmailModel(
        From $from,
        EmailHolderInterface|array $recipients,
        EmailTemplateCriteria|string $templateName,
        array $templateParams = [],
        array $templateContext = []
    ): EmailModel {
        $recipients = $this->normalizeRecipients($recipients);

        $renderedEmailTemplate = $this->renderEmailTemplate(
            $from,
            $recipients,
            $templateName,
            $templateParams,
            $templateContext
        );

        $emailModel = $this->createBaseEmailModel($from, $recipients, $renderedEmailTemplate, $templateParams);

        $this->attachTemplateAttachments($emailModel, $renderedEmailTemplate, $templateParams);

        return $emailModel;
    }

    /**
     * @param EmailHolderInterface|array<EmailHolderInterface> $recipients
     * @return array<EmailHolderInterface>
     */
    private function normalizeRecipients(EmailHolderInterface|array $recipients): array
    {
        return !is_array($recipients) ? [$recipients] : $recipients;
    }

    /**
     * @param From $from
     * @param array<EmailHolderInterface> $recipients
     * @param EmailTemplateCriteria|string $templateName
     * @param array<string,mixed> $templateParams
     * @param array<string,mixed> $templateContext
     *
     * @return EmailTemplateModel
     */
    private function renderEmailTemplate(
        From $from,
        array $recipients,
        EmailTemplateCriteria|string $templateName,
        array $templateParams,
        array $templateContext
    ): EmailTemplateModel {
        $templateContext += $this->emailTemplateContextProvider
            ->getTemplateContext($from, $recipients, $templateName, $templateParams);

        return $this->renderedEmailTemplateProvider
            ->findAndRenderEmailTemplate($templateName, $templateParams, $templateContext);
    }

    /**
     * @param From $from
     * @param array<EmailHolderInterface> $recipients
     * @param EmailTemplateModel $renderedEmailTemplate
     * @param array<string,mixed> $templateParams
     *
     * @return EmailModel
     */
    private function createBaseEmailModel(
        From $from,
        array $recipients,
        EmailTemplateModel $renderedEmailTemplate,
        array $templateParams
    ): EmailModel {
        $emailModel = $this->emailModelFactory->getEmail();

        $this->setEmailModelOrganization($emailModel, $templateParams);
        $this->setEmailModelOrigin($emailModel, $from);
        $this->setEmailModelBasicProperties($emailModel, $recipients, $renderedEmailTemplate);

        return $emailModel;
    }

    private function setEmailModelOrganization(EmailModel $emailModel, array $templateParams): void
    {
        if (!isset($templateParams['entity'])) {
            return;
        }

        $entityOrganization = $this->entityOwnerAccessor->getOrganization($templateParams['entity']);
        if ($entityOrganization !== null) {
            $emailModel->setOrganization($entityOrganization);
        }
    }

    private function setEmailModelOrigin(EmailModel $emailModel, From $from): void
    {
        $emailModel->setFrom($from->toString());

        $emailOrigin = $this->emailOriginHelper->getEmailOrigin(
            $emailModel->getFrom(),
            $emailModel->getOrganization()
        );

        if ($emailOrigin !== null) {
            $emailModel->setOrigin($emailOrigin);
        }
    }

    /**
     * @param EmailModel $emailModel
     * @param array<EmailHolderInterface> $recipients
     * @param EmailTemplateModel $renderedEmailTemplate
     */
    private function setEmailModelBasicProperties(
        EmailModel $emailModel,
        array $recipients,
        EmailTemplateModel $renderedEmailTemplate
    ): void {
        $emailModel->setTo(
            array_map(static fn (EmailHolderInterface $emailHolder) => $emailHolder->getEmail(), $recipients)
        );
        $emailModel->setSubject($renderedEmailTemplate->getSubject());
        $emailModel->setBody($renderedEmailTemplate->getContent());
        $emailModel->setType(
            $renderedEmailTemplate->getType() === EmailTemplateInterface::TYPE_HTML ? 'html' : 'text'
        );
    }

    /**
     * @param EmailModel $emailModel
     * @param EmailTemplateModel $renderedEmailTemplate
     * @param array<string,mixed> $templateParams
     */
    private function attachTemplateAttachments(
        EmailModel $emailModel,
        EmailTemplateModel $renderedEmailTemplate,
        array $templateParams
    ): void {
        foreach ($renderedEmailTemplate->getAttachments() as $emailTemplateAttachment) {
            $emailAttachmentModels = $this->emailAttachmentModelFactory
                ->createEmailAttachmentModels($emailTemplateAttachment, $templateParams);

            if (!$emailAttachmentModels) {
                continue;
            }

            $emailAttachmentEntities = $this->emailAttachmentEntityFactory
                ->createEmailAttachmentEntities($emailTemplateAttachment, $templateParams);

            if (!$emailAttachmentEntities) {
                continue;
            }

            $this->addValidAttachmentsToEmailModel($emailModel, $emailAttachmentModels, $emailAttachmentEntities);
        }
    }

    /**
     * @param EmailModel $emailModel
     * @param array<EmailAttachmentModel> $emailAttachmentModels
     * @param array<EmailAttachment> $emailAttachmentEntities
     */
    private function addValidAttachmentsToEmailModel(
        EmailModel $emailModel,
        array $emailAttachmentModels,
        array $emailAttachmentEntities
    ): void {
        foreach ($emailAttachmentModels as $index => $emailAttachmentModel) {
            if (!isset($emailAttachmentEntities[$index])) {
                continue;
            }

            $emailAttachmentModel->setEmailAttachment($emailAttachmentEntities[$index]);
            $emailModel->addAttachment($emailAttachmentModel);
        }
    }
}
