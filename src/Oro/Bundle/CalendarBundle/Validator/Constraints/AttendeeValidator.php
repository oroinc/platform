<?php

namespace Oro\Bundle\CalendarBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\CalendarBundle\Entity\Attendee as AttendeeEntity;

class AttendeeValidator extends ConstraintValidator
{
    /**
     * @param AttendeeEntity $value
     * @param Attendee $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value->getDisplayName() || $value->getEmail()) {
            return;
        }

        $this->context->addViolation($constraint->message);
    }
}
