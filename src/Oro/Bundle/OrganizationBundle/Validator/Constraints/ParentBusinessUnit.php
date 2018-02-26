<?php

namespace Oro\Bundle\OrganizationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint that checks that parent for the business unit is not his child.
 *
 * @Annotation
 */
class ParentBusinessUnit extends Constraint
{
    public $message = "Business Unit cannot have a child as a Parent Business Unit.";

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'parent_business_unit_validator';
    }

    /**
     * @inheritdoc
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
