<?php

namespace Oro\Bundle\CalendarBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class DateEarlierThanValidator extends ConstraintValidator
{

    public function validate($value, Constraint $constraint)
    {
        /** @var DateEarlierThan $constraint */
        if (!$value instanceof \DateTime) {
            throw new UnexpectedTypeException($value, 'DateTime');
        }

        $valueCompare = $this->context->getRoot()->get($constraint->field)->getData();

        if (!$valueCompare instanceof \DateTime) {
            throw new UnexpectedTypeException($value, 'DateTime');
        }

        if ($value->getTimestamp() > $valueCompare->getTimestamp()) {
            $this->context->addViolation($constraint->message, array('{{ field }}' => $constraint->field));
        }

    }
} 