<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;

/**
 * Gets SMTP configuration settings from system config and provides {@see SmtpSettings} object.
 */
class SmtpSettingsProvider extends AbstractSmtpSettingsProvider
{
    /**
     * {@inheritdoc}
     */
    public function getSmtpSettings($scopeIdentifier = null): SmtpSettings
    {
        return $this->getConfigurationSmtpSettings($scopeIdentifier);
    }
}
