<?php

namespace Oro\Bundle\EmailBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class EmailTemplateSyntax extends Constraint
{
    public $message = 'The template for {{ field }} ({{ locale }}) has syntax error: {{ error }}';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_email.email_template_syntax_validator';
    }
}
