<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class NotSqlKeyword extends Constraint
{
    public $message = "This value should not be the reserved SQL word.";

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return NotSqlKeywordValidator::ALIAS;
    }
}
