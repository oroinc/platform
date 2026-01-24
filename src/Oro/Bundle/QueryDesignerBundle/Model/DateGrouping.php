<?php

namespace Oro\Bundle\QueryDesignerBundle\Model;

/**
 * Represents date-based grouping configuration for query results.
 *
 * This class manages the configuration for grouping query results by date fields.
 * It allows specifying which date field to group by and provides options to control
 * filtering behavior, such as skipping empty periods and applying date group filters.
 * This is essential for time-series analysis and date-based aggregations in reports.
 */
class DateGrouping
{
    /**
     * @var string
     */
    protected $fieldName;

    /**
     * @var bool
     */
    protected $useSkipEmptyPeriodsFilter;

    /**
     * @var bool
     */
    protected $useDateGroupFilter;

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * @param string $fieldName
     * @return $this
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;

        return $this;
    }

    /**
     * @return bool
     */
    public function getUseSkipEmptyPeriodsFilter()
    {
        return $this->useSkipEmptyPeriodsFilter;
    }

    /**
     * @param bool $useSkipEmptyPeriodsFilter
     * @return $this
     */
    public function setUseSkipEmptyPeriodsFilter($useSkipEmptyPeriodsFilter)
    {
        $this->useSkipEmptyPeriodsFilter = $useSkipEmptyPeriodsFilter;

        return $this;
    }

    /**
     * @return bool
     */
    public function getUseDateGroupFilter()
    {
        return $this->useDateGroupFilter;
    }

    /**
     * @param bool $useDateGroupFilter
     * @return $this
     */
    public function setUseDateGroupFilter($useDateGroupFilter)
    {
        $this->useDateGroupFilter = $useDateGroupFilter;

        return $this;
    }
}
