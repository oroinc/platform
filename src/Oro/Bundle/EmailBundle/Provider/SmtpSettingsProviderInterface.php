<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;

interface SmtpSettingsProviderInterface
{
    /**
     * Gets SMTP settings model object
     *
     * @param null|int|object $scopeIdentifier
     *
     * @return SmtpSettings
     */
    public function getSmtpSettings($scopeIdentifier = null);
}
