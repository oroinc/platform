<?php

namespace Oro\Bundle\EmailBundle\Mailer\Checker;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\EmailBundle\Mailer\DirectMailer;

/**
 * Checks that connection can be established with the given SmtpSettings.
 */
class SmtpSettingsChecker
{
    /** @var DirectMailer */
    protected $directMailer;

    /**
     * SmtpSettingsChecker constructor.
     */
    public function __construct(DirectMailer $directMailer)
    {
        $this->directMailer = $directMailer;
    }

    /**
     * @param SmtpSettings $smtpSettings
     *
     * @return string
     */
    public function checkConnection(SmtpSettings $smtpSettings)
    {
        if (!$smtpSettings->isEligible()) {
            return 'Not eligible SmtpSettings are given';
        }

        $error = '';
        $this->directMailer->afterPrepareSmtpTransport($smtpSettings);

        try {
            $this->directMailer->getTransport()->start();
        } catch (\Swift_TransportException $e) {
            $error = $e->getMessage();
        }

        return $error;
    }
}
