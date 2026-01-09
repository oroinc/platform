<?php

namespace Oro\Bundle\OrganizationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for validating that a business unit cannot be set as its own parent.
 *
 * This constraint ensures data integrity by preventing circular ownership relationships
 * where a business unit would be assigned as its own parent, which would create an invalid
 * organizational hierarchy. The constraint is applied at the class level to validate the
 * entire BusinessUnit entity.
 */
class BusinessUnitOwner extends Constraint
{
    public $message = "Business Unit can't set self as Parent.";

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
