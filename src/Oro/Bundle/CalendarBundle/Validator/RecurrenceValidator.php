<?php

namespace Oro\Bundle\CalendarBundle\Validator;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Strategy\Recurrence\StrategyInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class RecurrenceValidator extends ConstraintValidator
{
    /** @var StrategyInterface  */
    protected $recurrenceStrategy;

    /**
     * RecurrenceValidator constructor.
     *
     * @param StrategyInterface $recurrenceStrategy
     */
    public function __construct(StrategyInterface $recurrenceStrategy)
    {
        $this->recurrenceStrategy = $recurrenceStrategy;
    }

    /**
     * Validates recurrence according to its recurrenceType.
     *
     * @param Recurrence $value
     *
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value->getEndTime() !== null && $value->getEndTime() < $value->getStartTime()) {
            $this->context->addViolation("Parameter 'endTime' date can't be earlier than startTime date.");
        }

        if ($errorMessage = $this->recurrenceStrategy->getValidationErrorMessage($value)) {
            $this->context->addViolation($errorMessage);
        }
    }
}
