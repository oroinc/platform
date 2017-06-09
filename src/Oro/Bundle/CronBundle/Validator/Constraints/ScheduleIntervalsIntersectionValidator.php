<?php

namespace Oro\Bundle\CronBundle\Validator\Constraints;

use Oro\Bundle\CronBundle\Entity\ScheduleIntervalInterface;
use Oro\Bundle\CronBundle\Form\Type\ScheduleIntervalType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ScheduleIntervalsIntersectionValidator extends ConstraintValidator
{
    /**
     * @param ScheduleIntervalInterface[] $scheduleIntervals The value that should be validated
     * @param Constraint|ScheduleIntervalsIntersection $constraint The constraint for the validation
     */
    public function validate($scheduleIntervals, Constraint $constraint)
    {
        if (!$this->isIterable($scheduleIntervals)) {
            throw new \InvalidArgumentException('Constraint value should be iterable');
        }

        foreach ($scheduleIntervals as $index => $scheduleInterval) {
            if ($this->hasIntersection($scheduleIntervals, $scheduleInterval)) {
                $path = sprintf('[%d].%s', $index, ScheduleIntervalType::ACTIVE_AT_FIELD);
                $this->context
                    ->buildViolation($constraint->message, [])
                    ->atPath($path)
                    ->addViolation();
            }
        }
    }

    /**
     * @param ScheduleIntervalInterface[] $scheduleIntervals
     * @param ScheduleIntervalInterface $scheduleInterval
     * @return bool
     */
    protected function hasIntersection($scheduleIntervals, ScheduleIntervalInterface $scheduleInterval)
    {
        $aLeft = $scheduleInterval->getActiveAt();
        $aRight = $scheduleInterval->getDeactivateAt();

        foreach ($scheduleIntervals as $item) {
            if ($item === $scheduleInterval) {
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

    /**
     * @param mixed $var
     * @return bool
     */
    protected function isIterable($var)
    {
        return is_array($var) || $var instanceof \Traversable;
    }
}
