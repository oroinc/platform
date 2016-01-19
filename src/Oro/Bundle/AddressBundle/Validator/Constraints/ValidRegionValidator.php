<?php

namespace Oro\Bundle\AddressBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidRegionValidator extends ConstraintValidator
{
    const ALIAS = 'oro_address_valid_region';

    /**
     * {@inheritdoc}
     * @param ValidRegion $constraint
     */
    public function validate($entity, Constraint $constraint)
    {
        if ($entity->getCountry() && $entity->getCountry()->hasRegions() &&
            !$entity->getRegion() && !$entity->getRegionText()
        ) {
            // do not allow saving text region in case when region was checked from list
            // except when in base data region text existed
            // another way region_text field will be null, logic are placed in form listener
            $propertyPath = $this->context->getPropertyPath() . '.region';
            $this->context->addViolationAt(
                $propertyPath,
                $constraint->message,
                ['{{ country }}' => $entity->getCountry()->getName()]
            );
        }
    }
}
