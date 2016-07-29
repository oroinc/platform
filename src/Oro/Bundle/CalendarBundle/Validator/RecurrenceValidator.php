<?php

namespace Oro\Bundle\CalendarBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\CalendarBundle\Model\Recurrence;

class RecurrenceValidator extends ConstraintValidator
{
    /** @var Recurrence  */
    protected $recurrenceModel;

    /**
     * RecurrenceValidator constructor.
     *
     * @param Recurrence $recurrenceModel
     */
    public function __construct(Recurrence $recurrenceModel)
    {
        $this->recurrenceModel = $recurrenceModel;
    }

    /**
     * Validates recurrence according to its recurrenceType.
     *
     * @param \Oro\Bundle\CalendarBundle\Entity\Recurrence $value
     *
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value->getEndTime() !== null && $value->getEndTime() < $value->getStartTime()) {
            $this->context->addViolation("Parameter 'endTime' date can't be earlier than startTime date.");
        }

        if (!in_array($value->getRecurrenceType(), $this->recurrenceModel->getRecurrenceTypesValues())) {
            $this->context->addViolation(
                "Parameter 'recurrenceType' must have one of the values: {{ values }}.",
                ['{{ values }}' => implode(', ', $this->recurrenceModel->getRecurrenceTypesValues())]
            );
        }

        $dayOfWeekValues = $this->recurrenceModel->getDaysOfWeekValues();
        if ($value->getDayOfWeek() !== null
            && count(array_intersect($value->getDayOfWeek(), $dayOfWeekValues)) !== count($value->getDayOfWeek())
        ) {
            $this->context->addViolation(
                "Parameter 'dayOfWeek' can have values from the list: {{ values }}.",
                ['{{ values }}' => implode(', ', $dayOfWeekValues)]
            );
        }

        if ($errorMessage = $this->recurrenceModel->getValidationErrorMessage($value)) {
            $this->context->addViolation($errorMessage);
        }
    }
}
