<?php

namespace Oro\Bundle\EmailBundle\Factory;

use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Form\Model\Factory as EmailModelFactory;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
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

    private EmailOriginHelper $emailOriginHelper;

    private EntityOwnerAccessor $entityOwnerAccessor;

    public function __construct(
        EmailTemplateContextProvider $emailTemplateContextProvider,
        RenderedEmailTemplateProvider $renderedEmailTemplateProvider,
        EmailModelFactory $emailModelFactory,
        EmailOriginHelper $emailOriginHelper,
        EntityOwnerAccessor $entityOwnerAccessor
    ) {
        $this->emailTemplateContextProvider = $emailTemplateContextProvider;
        $this->renderedEmailTemplateProvider = $renderedEmailTemplateProvider;
        $this->emailModelFactory = $emailModelFactory;
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
        $recipients = !is_array($recipients) ? [$recipients] : $recipients;

        $templateContext += $this->emailTemplateContextProvider
            ->getTemplateContext($from, $recipients, $templateName, $templateParams);

        $renderedEmailTemplate = $this->renderedEmailTemplateProvider
            ->findAndRenderEmailTemplate($templateName, $templateParams, $templateContext);

        $emailModel = $this->emailModelFactory->getEmail();

        if (isset($templateParams['entity'])) {
            $entityOrganization = $this->entityOwnerAccessor->getOrganization($templateParams['entity']);
            if ($entityOrganization !== null) {
                $emailModel->setOrganization($entityOrganization);
            }
        }

        $emailModel->setFrom($from->toString());

        $emailOrigin = $this->emailOriginHelper->getEmailOrigin($emailModel->getFrom(), $emailModel->getOrganization());
        if ($emailOrigin !== null) {
            $emailModel->setOrigin($emailOrigin);
        }

        $emailModel->setTo(
            array_map(static fn (EmailHolderInterface $emailHolder) => $emailHolder->getEmail(), $recipients)
        );
        $emailModel->setSubject($renderedEmailTemplate->getSubject());
        $emailModel->setBody($renderedEmailTemplate->getContent());
        $emailModel->setType($renderedEmailTemplate->getType() === EmailTemplateInterface::TYPE_HTML ? 'html' : 'text');

        return $emailModel;
    }
}
