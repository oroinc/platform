<?php

namespace Oro\Bundle\EmailBundle\Model;

class AutoResponseRuleCondition
{
    /** @var string */
    protected $field;

    /** @var string */
    protected $filterType;

    /** @var string */
    protected $filterValue;

    /**
     * @param array $filter
     *
     * @return $this
     */
    public static function createFromFilter(array $filter)
    {
        $data = $filter['criterion']['data'];

        $condition = new static();
        $condition->field = $filter['columnName'];
        $condition->filterType = $data['type'];
        $condition->filterValue = $data['value'];

        return $condition;
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @return string
     */
    public function getFilterType()
    {
        return $this->filterType;
    }

    /**
     * @return string
     */
    public function getFilterValue()
    {
        return $this->filterValue;
    }
}
