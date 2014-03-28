<?php

namespace Oro\Bundle\CalendarBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DateEarlierThanValidator extends ConstraintValidator
{

    public function validate($value, Constraint $constraint)
    {
        /** @var DateEarlierThan $constraint */
        if ($value instanceof \DateTime) {
            $valueCompare = $this->context->getRoot()->get($constraint->field)->getData();
            if ($valueCompare instanceof \DateTime && $value->getTimestamp() > $valueCompare->getTimestamp()) {
                $this->context->addViolation($constraint->message, array('{{ field }}' => $constraint->field));
            }
        }

    }
} 