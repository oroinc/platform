<?php

namespace Oro\Bundle\CronBundle\Entity;

use Doctrine\Common\Collections\Collection;

/**
 * Defines the contract for entities that contain a collection of schedule intervals.
 *
 * This interface is implemented by entities that support time-based activation and deactivation
 * through multiple schedule intervals. Common use cases include:
 * - Price lists with multiple active/inactive periods
 * - Promotions with scheduled availability windows
 * - Any feature requiring complex time-based scheduling
 *
 * Entities implementing this interface can be validated for schedule overlaps and checked
 * for active schedules at specific points in time using the ScheduleIntervalChecker service.
 */
interface ScheduleIntervalsAwareInterface
{
    /**
     * @return Collection|ScheduleIntervalInterface[]
     */
    public function getSchedules();
}
