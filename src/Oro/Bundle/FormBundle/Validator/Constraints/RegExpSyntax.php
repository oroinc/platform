<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint to check if a regular expression syntax is valid.
 */
class RegExpSyntax extends Constraint
{
    public const INVALID_REGEXP_SYNTAX_ERROR = '933d8ad0-7599-424b-9e65-72eb7ad2b3d1';

    protected static $errorNames = [
        self::INVALID_REGEXP_SYNTAX_ERROR => 'INVALID_REGEXP_SYNTAX_ERROR',
    ];

    public string $message = 'oro.form.regexp_syntax.error';
}
