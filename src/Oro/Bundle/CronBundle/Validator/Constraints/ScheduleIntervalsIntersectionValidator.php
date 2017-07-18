<?php

namespace Oro\Bundle\CronBundle\Validator\Constraints;

use Oro\Bundle\CronBundle\Entity\ScheduleIntervalInterface;
use Oro\Bundle\CronBundle\Form\Type\ScheduleIntervalType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ScheduleIntervalsIntersectionValidator extends ConstraintValidator
{
    /**
     * @param ScheduleIntervalInterface|mixed $value The value that should be validated
     * @param Constraint|ScheduleIntervalsIntersection $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof ScheduleIntervalInterface) {
            throw new \InvalidArgumentException(
                'Constraint value should be of type ' . ScheduleIntervalInterface::class
            );
        }

        $this->validateSchedules($value, $constraint);
    }

    /**
     * @param ScheduleIntervalInterface $validatedSchedule
     * @param Constraint $constraint
     */
    protected function validateSchedules(ScheduleIntervalInterface $validatedSchedule, Constraint $constraint)
    {
        if (null === $validatedSchedule->getScheduleIntervalsHolder()) {
            return;
        }

        $schedules = $validatedSchedule->getScheduleIntervalsHolder()->getSchedules();

        if (false === $this->hasIntersection($schedules, $validatedSchedule)) {
            return;
        }

        $form = $this->context->getRoot();

        /**
         * This is here to provide proper validation for API request on schedule PATCH
         * https://github.com/symfony/symfony/pull/10567
         */
        if ($form instanceof \Symfony\Component\Form\Form
            && $form->getConfig()->hasOption('api_context')) {
            $this->buildViolationOnApiForm($constraint);

            return;
        }

        $this->buildDefaultViolation($constraint);
    }

    /**
     * @param Constraint $constraint
     */
    protected function buildDefaultViolation(Constraint $constraint)
    {
        $this->context
            ->buildViolation($constraint->message)
            ->atPath(ScheduleIntervalType::ACTIVE_AT_FIELD)
            ->addViolation();
    }

    /**
     * @param Constraint $constraint
     */
    protected function buildViolationOnApiForm(Constraint $constraint)
    {
        $this->context
            ->buildViolation($constraint->message)
            ->addViolation();
    }

    /**
     * @param ScheduleIntervalInterface[] $collection
     * @param ScheduleIntervalInterface $schedule
     * @return bool
     */
    protected function hasIntersection($collection, ScheduleIntervalInterface $schedule)
    {
        $aLeft = $schedule->getActiveAt();
        $aRight = $schedule->getDeactivateAt();

        foreach ($collection as $item) {
            if ($item === $schedule) {
                continue;
            }

            $bLeft = $item->getActiveAt();
            $bRight = $item->getDeactivateAt();

            if ($this->isSegmentsIntersected($aLeft, $aRight, $bLeft, $bRight)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @param \DateTime|null $aLeft
     * @param \DateTime|null $aRight
     * @param \DateTime|null $bLeft
     * @param \DateTime|null $bRight
     * @return bool
     */
    protected function isSegmentsIntersected($aLeft, $aRight, $bLeft, $bRight)
    {
        if (($aRight === null && $bRight === null)
            || (null === $aRight && $bRight >= $aLeft)
            || (null === $bRight && $aRight >= $bLeft)
        ) {
            return true;
        }

        if ($aLeft === null && $bRight === null && $aRight < $bLeft) {
            return false;
        }

        return ((null === $aLeft || $aLeft <= $bRight) && (null === $bRight || $aRight >= $bLeft));
    }
}
