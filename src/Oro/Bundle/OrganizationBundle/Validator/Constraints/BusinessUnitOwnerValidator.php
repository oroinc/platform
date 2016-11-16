<?php

namespace Oro\Bundle\OrganizationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class BusinessUnitOwnerValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
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
