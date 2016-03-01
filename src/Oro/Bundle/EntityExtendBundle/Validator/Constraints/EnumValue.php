<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class EnumValue extends Constraint
{
    /**
     * @var string
     */
    public $message = 'This value should contain only alphabetic symbols, underscore, hyphen, spaces and numbers.';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return [self::CLASS_CONSTRAINT];
    }
}
