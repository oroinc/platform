<?php

namespace Oro\Bundle\CalendarBundle\Model\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;

interface StrategyInterface
{
    /**
     * Calculates occurrences dates according to recurrence rules and dates interval.
     *
     * @param Recurrence $recurrence
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return \DateTime[]
     */
    public function getOccurrences(Recurrence $recurrence, \DateTime $start, \DateTime $end);

    /**
     * Checks if strategy supports recurrence type.
     *
     * @param Recurrence $recurrence
     *
     * @return mixed
     */
    public function supports(Recurrence $recurrence);

    /**
     * Get name of recurrence strategy.
     *
     * @return string
     */
    public function getName();

    /**
     * Returns textual representation of recurrence rules.
     *
     * @param Recurrence $recurrence
     *
     * @return mixed
     */
    public function getRecurrencePattern(Recurrence $recurrence);
}
