<?php

namespace Oro\Bundle\ImapBundle\Validator\Constraints;

use Oro\Bundle\ImapBundle\Validator\ImapConnectionConfigurationValidator;
use Symfony\Component\Validator\Constraint;

/**
 * The constraint validates that IMAP connection can be established
 */
class ImapConnectionConfiguration extends Constraint
{
    /** @var string */
    public $message = 'oro.imap.validator.configuration.connection.imap';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return ImapConnectionConfigurationValidator::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
