<?php

namespace Oro\Bundle\EmailBundle\Mailer\Checker;

use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

/**
 * Extends {@see EsmtpTransport} to add ability to check if connection could be established.
 *
 * @internal
 */
class SmtpCheckingTransport extends EsmtpTransport
{
    public function check(string &$error = null): bool
    {
        try {
            $this->getStream()->initialize();
            // Read the opening SMTP greeting
            $this->executeCommand('', [220]);
            $this->doHeloCommand();

            return true;
        } catch (\RuntimeException $exception) {
            $error = $exception->getMessage();
        } finally {
            $this->getStream()->terminate();
        }

        return false;
    }
}
