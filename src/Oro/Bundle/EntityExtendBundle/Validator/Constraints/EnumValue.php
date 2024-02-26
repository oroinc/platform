<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * EnumValue constraint
 *
 * @Annotation
 */
#[Attribute]
class EnumValue extends Constraint
{
    /**
     * @var string
     */
    public $message = 'This value should contain only alphabetic symbols, underscore, hyphen, spaces and numbers.';

    /**
     * {@inheritdoc}
     */
    public function getTargets(): string|array
    {
        return [self::CLASS_CONSTRAINT];
    }
}
