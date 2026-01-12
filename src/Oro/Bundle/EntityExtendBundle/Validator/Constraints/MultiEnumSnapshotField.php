<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for validating multi-enum snapshot field names.
 *
 * This constraint ensures that the names of snapshot fields (automatically created fields
 * that store serialized multi-select enum values) do not conflict with existing multi-select
 * field names or other field names in the entity. It prevents naming conflicts that could
 * cause confusion or data integrity issues.
 */
class MultiEnumSnapshotField extends Constraint
{
    /** @var string */
    public $duplicateSnapshotMessage = 'This field name is duplicated snapshot of multi-select.';

    /** @var string */
    public $duplicateFieldMessage = 'This multi-select name is duplicated a field.';

    #[\Override]
    public function validatedBy(): string
    {
        return MultiEnumSnapshotFieldValidator::ALIAS;
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
