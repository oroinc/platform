<?php

namespace Oro\Bundle\AddressBundle\Validator\Constraints;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
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
        $country = $entity->getCountry();
        $region = $entity->getRegion();

        if ($country && $country->hasRegions() &&
            !$region && !$entity->getRegionText()
        ) {
            // do not allow saving text region in case when region was checked from list
            // except when in base data region text existed
            // another way region_text field will be null, logic are placed in form listener
            $this->context->addViolationAt(
                'region',
                $constraint->message,
                ['{{ country }}' => $country->getName()]
            );
        }

        if (!$this->regionBelongsToCountry($country, $region)) {
            $this->context->addViolation(
                'oro.address.validation.invalid_country_region',
                [
                    '{{ region }}' => $region->getName(),
                    '{{ country }}' => $country->getName(),
                ]
            );
        }
    }

    /**
     * This is needed to prevent setting for example region Berlin to country Romania
     *
     * @param Country|null $country
     * @param Region|null $region
     * @return bool
     */
    private function regionBelongsToCountry($country, $region)
    {
        // we can make this check only if elements are of the correct type
        if ($country instanceof Country &&
            $region instanceof Region) {
            return $country->getRegions()->contains($region);
        }

        return true;
    }
}
