<?php

namespace Oro\Bundle\EmailBundle\Tests\Behat\Mock\Mailer;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\EmailBundle\Mailer\Checker\SmtpSettingsChecker;

/**
 * Reports a successful connection for the well-known test SMTP server without a network call:
 * host "smtp.example.org", port 2525, encryption SSL or none,
 * either the "test_user"/"test_password" credentials or no credentials at all.
 * Any other settings are checked by the decorated checker.
 */
class SmtpSettingsCheckerStub extends SmtpSettingsChecker
{
    private const HOST = 'smtp.example.org';
    private const PORT = '2525';
    private const ENCRYPTIONS = ['ssl', ''];
    private const USERNAME = 'test_user';
    private const PASSWORD = 'test_password';

    private SmtpSettingsChecker $smtpSettingsChecker;

    /**
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(SmtpSettingsChecker $smtpSettingsChecker)
    {
        $this->smtpSettingsChecker = $smtpSettingsChecker;
    }

    #[\Override]
    public function checkConnection(SmtpSettings $smtpSettings, ?string &$error = null): bool
    {
        if ($this->isTestServer($smtpSettings) && $this->hasTestCredentials($smtpSettings)) {
            return true;
        }

        return $this->smtpSettingsChecker->checkConnection($smtpSettings, $error);
    }

    private function isTestServer(SmtpSettings $smtpSettings): bool
    {
        return
            $smtpSettings->getHost() === self::HOST
            && $smtpSettings->getPort() == self::PORT
            && \in_array((string)$smtpSettings->getEncryption(), self::ENCRYPTIONS, true);
    }

    private function hasTestCredentials(SmtpSettings $smtpSettings): bool
    {
        $username = (string)$smtpSettings->getUsername();
        $password = (string)$smtpSettings->getPassword();

        // unauthenticated connection: only the host and the port are required
        if ('' === $username && '' === $password) {
            return true;
        }

        return $username === self::USERNAME && $password === self::PASSWORD;
    }
}
