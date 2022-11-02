<?php

namespace Oro\Bundle\EmailBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check that an email template does not have syntax errors.
 */
class EmailTemplateSyntax extends Constraint
{
    public string $message = 'The template for {{ field }} ({{ locale }}) has syntax error: {{ error }}';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
