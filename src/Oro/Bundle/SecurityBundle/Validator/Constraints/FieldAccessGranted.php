<?php

namespace Oro\Bundle\SecurityBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for checking the field ACL.
 */
class FieldAccessGranted extends Constraint
{
    public string $message = 'You have no access to modify this field.';
}
