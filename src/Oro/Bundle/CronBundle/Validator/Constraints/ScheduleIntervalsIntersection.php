<?php

namespace Oro\Bundle\CronBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check that schedule intervals are not intersected.
 */
class ScheduleIntervalsIntersection extends Constraint
{
    public $message = 'oro.cron.validators.schedule_intervals_overlap.message';

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return [self::CLASS_CONSTRAINT];
    }
}
