<?php

namespace Oro\Bundle\EntityConfigBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for validating attributes within attribute family groups.
 *
 * This constraint validates that attributes are not duplicated across groups and that all system attributes
 * required by the entity are included in the attribute family's groups.
 */
class GroupAttributes extends Constraint
{
    /**
     * @var string
     */
    public $duplicateAttributesMessage = 'oro.entity_config.validator.attribute_family.duplicate_attributes';

    /**
     * @var string
     */
    public $missingSystemAttributesMessage = 'oro.entity_config.validator.attribute_family.missing_system_attributes';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }

    #[\Override]
    public function validatedBy(): string
    {
        return GroupAttributesValidator::ALIAS;
    }
}
