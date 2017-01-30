<?php

namespace Oro\Bundle\QueryDesignerBundle\Model;

class DateGrouping
{
    /**
     * @var string
     */
    protected $fieldName;

    /**
     * @var string
     */
    protected $notNullableField;

    /**
     * @var bool
     */
    protected $useSkipEmptyPeriodsFilter;

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
     * @return string
     */
    public function getNotNullableField()
    {
        return $this->notNullableField;
    }

    /**
     * @param string $notNullableField
     * @return $this
     */
    public function setNotNullableField($notNullableField)
    {
        $this->notNullableField = $notNullableField;

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
}
