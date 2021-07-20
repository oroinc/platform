<?php

namespace Oro\Bundle\CronBundle\Validator\Constraints;

use Oro\Bundle\CronBundle\Entity\ScheduleIntervalInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * This validator is used to check that schedule intervals are not intersected
 */
class ScheduleIntervalsIntersectionValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ScheduleIntervalsIntersection) {
            throw new UnexpectedTypeException($constraint, ScheduleIntervalsIntersection::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof ScheduleIntervalInterface) {
            throw new UnexpectedTypeException($value, ScheduleIntervalInterface::class);
        }

        $this->validateSchedules($value, $constraint);
    }

    private function validateSchedules(ScheduleIntervalInterface $validatedSchedule, Constraint $constraint): void
    {
        if (null === $validatedSchedule->getScheduleIntervalsHolder()) {
            return;
        }

        $schedules = $validatedSchedule->getScheduleIntervalsHolder()->getSchedules();
        if (false === $this->hasIntersection($schedules, $validatedSchedule)) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->addViolation();
    }

    /**
     * @param ScheduleIntervalInterface[]|iterable $collection
     * @param ScheduleIntervalInterface            $schedule
     *
     * @return bool
     */
    private function hasIntersection(iterable $collection, ScheduleIntervalInterface $schedule): bool
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
     */
    private function isSegmentsIntersected(
        ?\DateTimeInterface $aLeft,
        ?\DateTimeInterface $aRight,
        ?\DateTimeInterface $bLeft,
        ?\DateTimeInterface $bRight
    ): bool {
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
