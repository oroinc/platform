<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint file for AttributeFieldValidator class.
 */
class AttributeField extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.entity_extend.attribute_field.message';

    #[\Override]
    public function validatedBy(): string
    {
        return AttributeFieldValidator::ALIAS;
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
