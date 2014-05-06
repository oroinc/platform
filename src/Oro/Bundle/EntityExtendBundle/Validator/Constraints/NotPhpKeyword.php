<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class NotPhpKeyword extends Constraint
{
    public $message = "This value should not be the reserved PHP word.";
}
