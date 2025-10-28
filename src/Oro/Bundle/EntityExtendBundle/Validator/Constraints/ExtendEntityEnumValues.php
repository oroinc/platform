<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that enum and multiEnum field values exist in the system for extended entities.
 *
 * This constraint ensures that:
 * - Single enum fields contain only valid enum option references.
 * - Multi-enum fields contain only valid enum option references in their collections.
 * - Invalid or non-existent enum options are detected and reported during validation.
 *
 * The constraint is automatically applied to serialized enum fields through
 * ExtendEntitySerializedEnumValidatorPass compiler pass.
 */
class ExtendEntityEnumValues extends Constraint
{
    public string $message = 'oro.entity_extend.validator.enum_option.not_found';

    #[\Override]
    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }

    #[\Override]
    public function validatedBy(): string
    {
        return ExtendEntityEnumValuesValidator::class;
    }
}
