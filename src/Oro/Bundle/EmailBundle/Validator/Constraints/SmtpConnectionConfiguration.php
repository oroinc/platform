<?php

namespace Oro\Bundle\EmailBundle\Validator\Constraints;

use Oro\Bundle\EmailBundle\Validator\SmtpConnectionConfigurationValidator;
use Symfony\Component\Validator\Constraint;

/**
 * The constraint checks that SMTP connection can be established with provided configuration parameters
 */
class SmtpConnectionConfiguration extends Constraint
{
    /** @var string */
    public $message = 'oro.email.validator.configuration.connection.smtp';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return SmtpConnectionConfigurationValidator::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
