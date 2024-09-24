<?php

namespace Oro\Bundle\OrganizationBundle\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * Constraint that checks that parent for the business unit is not his child.
 *
 * @Annotation
 */
#[Attribute]
class ParentBusinessUnit extends Constraint
{
    public $message = "Business Unit cannot have a child as a Parent Business Unit.";

    #[\Override]
    public function validatedBy(): string
    {
        return 'parent_business_unit_validator';
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
