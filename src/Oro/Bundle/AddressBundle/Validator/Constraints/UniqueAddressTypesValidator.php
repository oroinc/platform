<?php

namespace Oro\Bundle\AddressBundle\Validator\Constraints;

use Oro\Bundle\AddressBundle\Entity\AbstractTypedAddress;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueAddressTypesValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!is_array($value) && !($value instanceof \Traversable && $value instanceof \ArrayAccess)) {
            throw new UnexpectedTypeException($value, 'array or Traversable and ArrayAccess');
        }

        $repeatedTypes = [];
        $collectedTypes = [];

        /** @var AbstractTypedAddress $address */
        foreach ($value as $address) {
            if (!$address instanceof AbstractTypedAddress) {
                throw new UnexpectedTypeException($value, 'Oro\Bundle\AddressBundle\Entity\AbstractTypedAddress');
            }

            if ($address->isEmpty()) {
                continue;
            }

            foreach ($address->getTypes() as $type) {
                if (isset($collectedTypes[$type->getName()])) {
                    $repeatedTypes[] = $type->getLabel();
                }
                $collectedTypes[$type->getName()] = true;
            }
        }

        if ($repeatedTypes) {
            /** @var UniqueAddressTypes $constraint */
            $this->context->addViolation(
                $constraint->message,
                ['{{ types }}' => '"' . implode('", "', $repeatedTypes) . '"']
            );
        }
    }
}
