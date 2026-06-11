<?php

namespace Oro\Bundle\EmailBundle\Mailer\Checker;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\EmailBundle\Mailer\Transport\DsnFromSmtpSettingsFactory;

/**
 * Checks that connection can be established with the given SmtpSettings.
 */
class SmtpSettingsChecker
{
    public function __construct(
        private readonly DsnFromSmtpSettingsFactory $dsnFromSmtpSettingsFactory,
        private readonly ConnectionCheckerInterface $connectionChecker
    ) {
    }

    public function checkConnection(SmtpSettings $smtpSettings, ?string &$error = null): bool
    {
        if (!$smtpSettings->isEligible()) {
            $error = 'Not eligible SmtpSettings are given';

            return false;
        }

        if (!$this->connectionChecker->checkConnection($this->dsnFromSmtpSettingsFactory->create($smtpSettings))) {
            $error = 'A connection could not be established';

            return false;
        }

        return true;
    }
}
