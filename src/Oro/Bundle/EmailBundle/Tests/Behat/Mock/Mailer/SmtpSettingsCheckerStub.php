<?php

namespace Oro\Bundle\EmailBundle\Tests\Behat\Mock\Mailer;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\EmailBundle\Mailer\Checker\SmtpSettingsChecker;

class SmtpSettingsCheckerStub extends SmtpSettingsChecker
{
    private SmtpSettingsChecker $smtpSettingsChecker;

    /**
     * @param SmtpSettingsChecker $smtpSettingsChecker
     *
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(SmtpSettingsChecker $smtpSettingsChecker)
    {
        $this->smtpSettingsChecker = $smtpSettingsChecker;
    }

    public function checkConnection(SmtpSettings $smtpSettings, string &$error = null): bool
    {
        if ($smtpSettings->getHost() === 'smtp.example.org' &&
            $smtpSettings->getPort() == '2525' &&
            $smtpSettings->getEncryption() === 'ssl' &&
            $smtpSettings->getUsername() === 'test_user' &&
            $smtpSettings->getPassword() === 'test_password') {
            return true;
        }

        return $this->smtpSettingsChecker->checkConnection($smtpSettings, $error);
    }
}
