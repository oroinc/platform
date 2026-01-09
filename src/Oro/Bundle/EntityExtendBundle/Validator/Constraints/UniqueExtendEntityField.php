<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for validating that extended entity field names are unique.
 *
 * This constraint ensures that new custom fields do not have names that conflict with
 * existing fields in the entity. It detects both exact name matches and similar names
 * that could cause confusion or naming conflicts.
 */
class UniqueExtendEntityField extends Constraint
{
    /** @var string */
    public $sameFieldMessage = 'A field with this name is already exist.';

    /** @var string */
    public $similarFieldMessage = 'This name conflicts with existing \'{{ field }}\' field.';

    #[\Override]
    public function validatedBy(): string
    {
        return UniqueExtendEntityFieldValidator::ALIAS;
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
