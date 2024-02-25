<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * NotPhpKeyword constraint
 *
 * @Annotation
 */
#[Attribute]
class NotPhpKeyword extends Constraint
{
    public $message = 'This value should not be the reserved PHP word.';
}
