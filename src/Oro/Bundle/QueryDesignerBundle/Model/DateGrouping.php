<?php

namespace Oro\Bundle\QueryDesignerBundle\Model;

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
