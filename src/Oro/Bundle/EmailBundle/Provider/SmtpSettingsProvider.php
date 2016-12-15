<?php

namespace Oro\Bundle\EmailBundle\Provider;

class SmtpSettingsProvider extends AbstractSmtpSettingsProvider
{
    /**
     * @inheritdoc
     */
    public function getSmtpSettings($scopeIdentifier = null)
    {
        return $this->getGlobalSmtpSettings();
    }
}
