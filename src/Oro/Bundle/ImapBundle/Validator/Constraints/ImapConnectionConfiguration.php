<?php

namespace Oro\Bundle\ImapBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * The constraint to check that IMAP connection can be established with parameters from UserEmailOrigin.
 */
class ImapConnectionConfiguration extends Constraint
{
    public string $message = 'oro.imap.validator.configuration.connection.imap';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
