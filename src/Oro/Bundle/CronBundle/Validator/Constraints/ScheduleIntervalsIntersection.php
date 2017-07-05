<?php

namespace Oro\Bundle\CronBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ScheduleIntervalsIntersection extends Constraint
{
    const ALIAS = 'oro_cron_schedule_intervals_intersection_validator';

    /**
     * @var string
     */
    public $message = 'oro.cron.validators.schedule_intervals_overlap.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return self::ALIAS;
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return [self::CLASS_CONSTRAINT];
    }
}
