<?php

namespace Oro\Bundle\SecurityBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class FieldAccessGranted extends Constraint
{
    public $message = 'You have no access to modify this field.';
}
