<?php

namespace Oro\Bundle\AddressBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint can be used to check if frontend owners' organization and system organization are the same.
 */
class ValidOrganization extends Constraint
{
    public $message = 'oro.address.validation.invalid_customer_and_address_organization';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
