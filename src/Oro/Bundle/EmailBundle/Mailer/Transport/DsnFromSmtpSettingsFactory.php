<?php

namespace Oro\Bundle\EmailBundle\Mailer\Transport;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Symfony\Component\Mailer\Transport\Dsn;

/**
 * Creates SMTP DSN from {@see SmtpSettings}.
 */
class DsnFromSmtpSettingsFactory
{
    /**
     * Creates SMTP DSN from SmtpSettings.
     *
     * @param SmtpSettings $smtpSettings
     * @return Dsn
     */
    public function create(SmtpSettings $smtpSettings): Dsn
    {
        return new Dsn(
            strtolower((string)$smtpSettings->getEncryption()) === 'ssl' ? 'smtps' : 'smtp',
            (string)$smtpSettings->getHost(),
            (string)$smtpSettings->getUsername(),
            (string)$smtpSettings->getPassword(),
            (int)$smtpSettings->getPort()
        );
    }
}
