<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption;

/**
 * Provides an interface for drivers to manage the date when a state was changed.
 */
interface StateDriverInterface
{
    /**
     * Saves the date when a state was changed.
     *
     * @param \DateTime|null $date
     */
    public function setChangeStateDate(\DateTime $date = null);

    /**
     * Returns the last date when a state was changed.
     *
     * @return \DateTime|null
     */
    public function getChangeStateDate();

    /**
     * Saves the date when a state was changed.
     * The value will not be overwritten if it differs from the original for less than $gapPeriod seconds
     *
     * @param \DateTime $date
     * @param integer $gapPeriod Gap period in seconds
     *
     */
    public function setChangeStateDateWithTimeGap(\DateTime $date, $gapPeriod = 5);
}
