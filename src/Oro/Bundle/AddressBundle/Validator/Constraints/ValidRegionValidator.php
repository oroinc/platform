<?php

namespace Oro\Bundle\AddressBundle\Validator\Constraints;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * The validator for ValidRegion constraint.
 */
class ValidRegionValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     * @param AbstractAddress $entity
     * @param ValidRegion     $constraint
     */
    public function validate($entity, Constraint $constraint)
    {
        if (null === $entity) {
            return;
        }
        if (!$entity instanceof AbstractAddress) {
            throw new UnexpectedTypeException($entity, AbstractAddress::class);
        }

        $country = $entity->getCountry();
        $region = $entity->getRegion();
        if (null !== $country && null !== $region && !$country->getRegions()->contains($region)) {
            // prevent setting for example region Berlin to country Romania
            $this->context->addViolation(
                $constraint->message,
                [
                    '{{ region }}'  => $region->getName(),
                    '{{ country }}' => $country->getName()
                ]
            );
        }
    }
}
