<?php

namespace Oro\Bundle\CalendarBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

class CalendarEventValidator extends ConstraintValidator
{
    /**
     * @param CalendarEvent $value
     *
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        $recurringEvent = $value->getRecurringEvent();
        if ($recurringEvent && $recurringEvent->getId() === $value->getId()) {
            $this->context->addViolation(
                "Parameter 'recurringEventId' can't have the same value as calendar event ID."
            );
        }

        if ($recurringEvent && $recurringEvent->getRecurrence() === null) {
            $this->context->addViolation("Parameter 'recurringEventId' can be set only for recurring calendar events.");
        }
    }
}
