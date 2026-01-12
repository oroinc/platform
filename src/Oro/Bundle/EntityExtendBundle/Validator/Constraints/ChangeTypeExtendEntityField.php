<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for validating that extended entity field types cannot be changed.
 *
 * This constraint prevents the modification of field types for extended entity fields
 * after they have been created. Changing a field type could lead to data loss or
 * inconsistencies, so this constraint enforces that field types remain immutable.
 */
class ChangeTypeExtendEntityField extends Constraint
{
    /** @var string */
    public $message = 'oro.entity_extend.change_type_not_allowed.message';

    #[\Override]
    public function validatedBy(): string
    {
        return ChangeTypeExtendEntityFieldValidator::ALIAS;
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
