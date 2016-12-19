<?php

namespace Oro\Bundle\EmailBundle\Mailer\Checker;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\EmailBundle\Mailer\DirectMailer;

class SmtpSettingsChecker
{
    /** @var DirectMailer */
    protected $directMailer;

    /**
     * SmtpSettingsChecker constructor.
     *
     * @param DirectMailer $directMailer
     */
    public function __construct(DirectMailer $directMailer)
    {
        $this->directMailer = $directMailer;
    }

    /**
     * @param SmtpSettings $smtpSettings
     *
     * @return bool|string
     */
    public function checkConnection(SmtpSettings $smtpSettings)
    {
        $error = false;
        $this->directMailer->postPrepareSmtpTransport($smtpSettings);

        try {
            $this->directMailer->getTransport()->start();
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        return $error;
    }
}
