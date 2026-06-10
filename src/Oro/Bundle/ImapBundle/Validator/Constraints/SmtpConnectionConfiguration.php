<?php

namespace Oro\Bundle\ImapBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * The constraint to check that SMTP connection can be established with parameters from UserEmailOrigin.
 */
class SmtpConnectionConfiguration extends Constraint
{
    public string $message = 'oro.imap.validator.configuration.connection.smtp';

    #[\Override]
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
