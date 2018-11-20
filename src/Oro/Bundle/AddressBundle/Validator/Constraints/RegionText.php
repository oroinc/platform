<?php

namespace Oro\Bundle\AddressBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint can be used to check that address regionText field
 * can have a value only for countries without predefined regions.
 */
class RegionText extends Constraint
{
    public $message = 'oro.address.validation.invalid_region_text';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
