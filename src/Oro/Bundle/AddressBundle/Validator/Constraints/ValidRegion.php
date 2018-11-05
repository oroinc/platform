<?php

namespace Oro\Bundle\AddressBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint can be used to check if an address region exists for a country.
 */
class ValidRegion extends Constraint
{
    public $message = 'oro.address.validation.invalid_country_region';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
