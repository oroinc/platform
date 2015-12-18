<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Ajax\MassDelete;

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
