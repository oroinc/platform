<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Ajax\MassDelete;

/**
 * Contains the result of mass delete limit validation.
 *
 * This class encapsulates information about the number of selected records, how many can
 * actually be deleted based on permissions and limits, and the maximum allowed deletion limit.
 */
class MassDeleteLimitResult
{
    /** @var int */
    protected $maxLimit = MassDeleteLimiter::MAX_DELETE_RECORDS;

    /** @var int */
    protected $selected;

    /** @var int */
    protected $deletable;

    /**
     * MassDeleteLimitResult constructor.
     *
     * @param int $selected
     * @param int $deletable
     * @param int $maxLimit
     */
    public function __construct($selected, $deletable, $maxLimit = MassDeleteLimiter::MAX_DELETE_RECORDS)
    {
        $this->selected  = (int)$selected;
        $this->deletable = (int)$deletable;
        $this->maxLimit  = (int)$maxLimit;
    }

    /**
     * Returns max amount of records which can be remove at once.
     *
     * @return int
     */
    public function getMaxLimit()
    {
        return $this->maxLimit;
    }

    /**
     * Returns amount of selected records.
     *
     * @return int
     */
    public function getSelected()
    {
        return $this->selected;
    }

    /**
     * Returns amount of records which current user able to remove.
     *
     * @return int
     */
    public function getDeletable()
    {
        return $this->deletable;
    }
}
