<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;

/**
 * Represents a service to get SMTP configuration settings.
 */
interface SmtpSettingsProviderInterface
{
    public function getSmtpSettings(object|int|null $scopeIdentifier = null): SmtpSettings;
}
