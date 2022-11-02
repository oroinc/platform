<?php

namespace Oro\Bundle\EmailBundle\Mailer\Checker;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\EmailBundle\Mailer\Transport\DsnFromSmtpSettingsFactory;

/**
 * Checks that connection can be established with the given SmtpSettings.
 */
class SmtpSettingsChecker
{
    private DsnFromSmtpSettingsFactory $dsnFromSmtpSettingsFactory;

    private ConnectionCheckerInterface $smtpConnectionChecker;

    public function __construct(
        DsnFromSmtpSettingsFactory $dsnFromSmtpSettingsFactory,
        ConnectionCheckerInterface $smtpConnectionChecker
    ) {
        $this->dsnFromSmtpSettingsFactory = $dsnFromSmtpSettingsFactory;
        $this->smtpConnectionChecker = $smtpConnectionChecker;
    }

    public function checkConnection(SmtpSettings $smtpSettings, string &$error = null): bool
    {
        if (!$smtpSettings->isEligible()) {
            $error = 'Not eligible SmtpSettings are given';

            return false;
        }

        $dsn = $this->dsnFromSmtpSettingsFactory->create($smtpSettings);

        return $this->smtpConnectionChecker->checkConnection($dsn, $error);
    }
}
