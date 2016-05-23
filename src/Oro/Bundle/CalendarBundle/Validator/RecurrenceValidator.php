<?php

namespace Oro\Bundle\CalendarBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Oro\Bundle\CalendarBundle\Model\Recurrence;

class RecurrenceValidator extends ConstraintValidator
{
    /** @var Recurrence  */
    protected $recurrenceModel;


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

        if ($errorMessage = $this->recurrenceModel->getValidationErrorMessage($value)) {
            $this->context->addViolation($errorMessage);
        }
    }
}
