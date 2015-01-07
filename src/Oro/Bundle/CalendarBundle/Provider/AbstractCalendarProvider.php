<?php

namespace Oro\Bundle\CalendarBundle\Provider;

abstract class AbstractCalendarProvider
{
    /** @var string[] */
    protected $extraFields = [];

    /**
     * @return string[]
     */
    public function getExtraFields()
    {
        return $this->extraFields;
    }

    /**
     * @param string[] $extraFields
     */
    public function setExtraField($extraFields)
    {
        $this->extraFields = $extraFields;
    }
}
