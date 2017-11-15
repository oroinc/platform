<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class Unchangeable extends Constraint
{
    public $message = 'Field cannot be changed once set';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return static::PROPERTY_CONSTRAINT;
    }
}
