<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * NotSqlKeyword constraint
 *
 * @Annotation
 */
#[Attribute]
class NotSqlKeyword extends Constraint
{
    public $message = 'This value should not be the reserved SQL word.';

    #[\Override]
    public function validatedBy(): string
    {
        return NotSqlKeywordValidator::ALIAS;
    }
}
