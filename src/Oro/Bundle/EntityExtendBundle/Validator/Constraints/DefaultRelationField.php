<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for validating default relation field names.
 *
 * This constraint ensures that the names of default relation fields (automatically created
 * fields that store the default related entity) do not conflict with existing relation names
 * or other field names in the entity. It prevents naming conflicts that could cause confusion
 * or data integrity issues.
 */
class DefaultRelationField extends Constraint
{
    /** @var string */
    public $duplicateRelationMessage = 'This name is duplicated default field of relation.';

    /** @var string */
    public $duplicateFieldMessage = 'This relation name is duplicated a field.';

    #[\Override]
    public function validatedBy(): string
    {
        return DefaultRelationFieldValidator::ALIAS;
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
