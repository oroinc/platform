<?php

namespace Oro\Bundle\AddressBundle\Validator\Constraints;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * The validator for RegionText constraint.
 */
class RegionTextValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     * @param AbstractAddress $entity
     * @param RegionText      $constraint
     */
    public function validate($entity, Constraint $constraint)
    {
        if (null === $entity) {
            return;
        }
        if (!$entity instanceof AbstractAddress) {
            throw new UnexpectedTypeException($entity, AbstractAddress::class);
        }

        if ($entity->getRegionText()) {
            $country = $entity->getCountry();
            if (null !== $country && $country->hasRegions()) {
                $this->context->addViolation($constraint->message);
            }
        }
    }
}
