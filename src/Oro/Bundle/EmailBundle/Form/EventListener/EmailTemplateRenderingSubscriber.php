<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Form\EventListener;

use Oro\Bundle\EmailBundle\Builder\Helper\EmailModelBuilderHelper;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Provider\EmailTemplateContextProvider;
use Oro\Bundle\EmailBundle\Provider\TranslatedEmailTemplateProvider;
use Oro\Bundle\NotificationBundle\Model\EmailAddressWithContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Fills the subject and body of an email model with the rendered email template specified in it.
 */
class EmailTemplateRenderingSubscriber implements EventSubscriberInterface
{
    private EmailModelBuilderHelper $emailModelBuilderHelper;

    private TranslatedEmailTemplateProvider $translatedEmailTemplateProvider;

    private EmailTemplateContextProvider $emailTemplateContextProvider;

    private EmailRenderer $emailRenderer;

    public function __construct(
        EmailModelBuilderHelper $emailModelBuilderHelper,
        TranslatedEmailTemplateProvider $translatedEmailTemplateProvider,
        EmailTemplateContextProvider $emailTemplateContextProvider,
        EmailRenderer $emailRenderer
    ) {
        $this->emailModelBuilderHelper = $emailModelBuilderHelper;
        $this->translatedEmailTemplateProvider = $translatedEmailTemplateProvider;
        $this->emailTemplateContextProvider = $emailTemplateContextProvider;
        $this->emailRenderer = $emailRenderer;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'onPreSetData',
        ];
    }

    public function onPreSetData(PreSetDataEvent $event): void
    {
        /** @var EmailModel|null $emailModel */
        $emailModel = $event->getData();
        if (!$emailModel instanceof EmailModel || !$emailModel->getTemplate()) {
            return;
        }

        if ($emailModel->getSubject() !== null && $emailModel->getBody() !== null) {
            return;
        }

        if (!$emailModel->getEntityClass() || !$emailModel->getEntityId()) {
            return;
        }

        $targetEntity = $this->emailModelBuilderHelper
            ->getTargetEntity($emailModel->getEntityClass(), $emailModel->getEntityId());
        $templateParams = ['entity' => $targetEntity];
        $emailTemplate = $emailModel->getTemplate();

        $emailTemplateCriteria = new EmailTemplateCriteria($emailTemplate->getName(), $emailTemplate->getEntityName());
        $templateContext = $this->emailTemplateContextProvider->getTemplateContext(
            From::emailAddress($emailModel->getFrom()),
            $this->createRecipients($emailModel->getTo(), $targetEntity),
            $emailTemplateCriteria,
            $templateParams
        );

        $translatedEmailTemplate = $this->translatedEmailTemplateProvider
            ->getTranslatedEmailTemplate($emailTemplate, $templateContext['localization'] ?? null);

        $renderedEmailTemplate = $this->emailRenderer
            ->renderEmailTemplate($translatedEmailTemplate, $templateParams, $templateContext);

        if (null === $emailModel->getSubject()) {
            $emailModel->setSubject($renderedEmailTemplate->getSubject());
        }
        if (null === $emailModel->getBody()) {
            $emailModel->setBody($renderedEmailTemplate->getContent());
        }
    }

    private function createRecipients(array $emailAddresses, object $targetEntity): array
    {
        return array_map(
            static fn (?string $emailAddress) => new EmailAddressWithContext((string) $emailAddress, $targetEntity),
            $emailAddresses
        );
    }
}
