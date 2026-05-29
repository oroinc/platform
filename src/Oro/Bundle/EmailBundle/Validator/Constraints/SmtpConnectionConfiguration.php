<?php

namespace Oro\Bundle\EmailBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * The constraint to check that SMTP connection can be established with provided configuration parameters.
 */
class SmtpConnectionConfiguration extends Constraint
{
    public string $message = 'oro.email.validator.configuration.connection.smtp';

    #[\Override]
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
