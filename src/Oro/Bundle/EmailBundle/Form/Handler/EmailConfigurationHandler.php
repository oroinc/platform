<?php

namespace Oro\Bundle\EmailBundle\Form\Handler;

use Oro\Bundle\ConfigBundle\Config\ConfigChangeSet;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Event\SmtpSettingsSaved;
use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Dispatches {@see SmtpSettingsSaved} event when email SMTP settings are changed.
 */
class EmailConfigurationHandler
{
    private EventDispatcherInterface $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function process(ConfigManager $manager, ConfigChangeSet $changeSet, FormInterface $form): void
    {
        if (!$changeSet->isChanged('oro_email.smtp_settings_host')
            && !$changeSet->isChanged('oro_email.smtp_settings_port')
            && !$changeSet->isChanged('oro_email.smtp_settings_encryption')
            && !$changeSet->isChanged('oro_email.smtp_settings_username')
            && !$changeSet->isChanged('oro_email.smtp_settings_password')
        ) {
            return;
        }

        if ($this->dispatcher->hasListeners(SmtpSettingsSaved::NAME)) {
            $this->dispatcher->dispatch(
                new SmtpSettingsSaved(new SmtpSettings(
                    $this->getFormFieldValue($form, 'smtp_settings_host'),
                    $this->getFormFieldValue($form, 'smtp_settings_port'),
                    $this->getFormFieldValue($form, 'smtp_settings_encryption'),
                    $this->getFormFieldValue($form, 'smtp_settings_username'),
                    $this->getFormFieldValue($form, 'smtp_settings_password')
                )),
                SmtpSettingsSaved::NAME
            );
        }
    }

    private function getFormFieldValue(FormInterface $form, string $field): mixed
    {
        $fieldName = 'oro_email' . ConfigManager::SECTION_VIEW_SEPARATOR . $field;
        if (!$form->has($fieldName)) {
            return null;
        }

        return $form->get($fieldName)->getData()['value'];
    }
}
