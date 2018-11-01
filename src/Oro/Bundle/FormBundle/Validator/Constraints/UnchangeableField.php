<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint can be used to protect changing a value of a field or an association.
 * The changing a value is allowed if the previous value was NULL.
 */
class UnchangeableField extends Constraint
{
    public $message = 'Field cannot be changed once set';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
