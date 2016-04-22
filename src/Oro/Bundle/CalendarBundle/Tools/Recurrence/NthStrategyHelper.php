<?php

namespace Oro\Bundle\CalendarBundle\Tools\Recurrence;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;

class NthStrategyHelper
{
    /** @var array */
    protected $instanceRelativeValues;

    /**
     * CreatedAtAwareTrait constructor.
     */
    public function __construct()
    {
        $this->instanceRelativeValues = [
            Recurrence::INSTANCE_FIRST => 'first',
            Recurrence::INSTANCE_SECOND => 'second',
            Recurrence::INSTANCE_THIRD => 'third',
            Recurrence::INSTANCE_FOURTH => 'fourth',
            Recurrence::INSTANCE_LAST => 'last',
        ];
    }

    /**
     * Returns recurrence instance relative value by its key.
     *
     * @param $key
     *
     * @return null|string
     */
    public function getInstanceRelativeValue($key)
    {
        return empty($this->instanceRelativeValues[$key]) ? null : $this->instanceRelativeValues[$key];
    }
}
