<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Oro\Bundle\FormBundle\Entity\PrimaryItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ContainsPrimaryValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!is_array($value) && !($value instanceof \Traversable && $value instanceof \ArrayAccess)) {
            throw new UnexpectedTypeException($value, 'array or Traversable and ArrayAccess');
        }
        if ($value instanceof AbstractLazyCollection && !$value->isInitialized()) {
            return;
        }

        $primaryItemsNumber = 0;
        $totalItemsNumber = 0;
        /** @var PrimaryItem $item */
        foreach ($value as $item) {
            if ($item instanceof PrimaryItem && $item->isPrimary()) {
                $primaryItemsNumber++;
            }
            $totalItemsNumber++;
        }

        if ($totalItemsNumber > 0 && $primaryItemsNumber != 1) {
            $this->context->addViolation($constraint->message);
        }
    }
}
