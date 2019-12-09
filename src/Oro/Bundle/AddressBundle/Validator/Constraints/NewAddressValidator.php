<?php

namespace Oro\Bundle\AddressBundle\Validator\Constraints;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that an entity is created with a new address.
 */
class NewAddressValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof NewAddress) {
            throw new UnexpectedTypeException($constraint, NewAddress::class);
        }

        if (!$value instanceof AbstractAddress) {
            return;
        }

        if (null !== $value->getId()) {
            $this->context->addViolation($constraint->message);
        }
    }
}
