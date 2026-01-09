<?php

namespace Oro\Bundle\OrganizationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the {@see BusinessUnitOwner} constraint to prevent circular ownership relationships.
 *
 * This validator checks that a business unit is not assigned as its own parent, handling
 * both persisted entities (by comparing IDs) and new entities (by comparing object identity).
 * It ensures the organizational hierarchy remains valid and prevents data integrity violations.
 */
class BusinessUnitOwnerValidator extends ConstraintValidator
{
    #[\Override]
    public function validate($value, Constraint $constraint)
    {
        $owner = $value->getOwner();
        if (null === $owner) {
            return;
        }

        if (null === $owner->getId() || null === $value->getId()) {
            if (null === $owner->getId() && null === $value->getId() && $value === $owner) {
                $this->context->addViolation($constraint->message);
            }
        } elseif ($value->getId() == $owner->getId()) {
            $this->context->addViolation($constraint->message);
        }
    }
}
