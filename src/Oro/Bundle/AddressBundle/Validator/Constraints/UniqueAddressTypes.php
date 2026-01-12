<?php

namespace Oro\Bundle\AddressBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for validating that addresses do not have duplicate types.
 *
 * This constraint ensures that within a collection of addresses, each address type
 * appears only once. When multiple addresses share the same type, the validation fails
 * with a message indicating which types are duplicated.
 */
class UniqueAddressTypes extends Constraint
{
    public $message = 'Several addresses have the same type {{ types }}.';
}
