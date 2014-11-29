<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;

class SystemCalendarConfigHelper
{
    /** @var string|boolean */
    protected $systemCalendarSupport;

    /**
     * @param string|boolean $systemCalendarSupport
     */
    public function __construct($systemCalendarSupport)
    {
        $this->transformConfigToArray($systemCalendarSupport);
    }

    /**
     * @return bool
     */
    public function isSystemCalendarSupported()
    {
        if (isset($this->systemCalendarSupport[SystemCalendar::CALENDAR_ALIAS])) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isPublicCalendarSupported()
    {
        if (isset($this->systemCalendarSupport[SystemCalendar::PUBLIC_CALENDAR_ALIAS])) {
            return true;
        }

        return false;
    }

    public function isSomeSystemCalendarSupported()
    {
        if ($this->systemCalendarSupport) {
            return true;
        }

        return false;
    }

    /**
     * @param string|boolean  $systemCalendarSupport
     */
    protected function transformConfigToArray($systemCalendarSupport)
    {
        $this->systemCalendarSupport = [];
        if ($systemCalendarSupport === true) {
            $this->systemCalendarSupport = [
                SystemCalendar::CALENDAR_ALIAS => true,
                SystemCalendar::PUBLIC_CALENDAR_ALIAS => true
            ];
        } elseif ($systemCalendarSupport === SystemCalendar::CALENDAR_ALIAS) {
            $this->systemCalendarSupport = [
                SystemCalendar::PUBLIC_CALENDAR_ALIAS => true,
            ];
        } elseif ($systemCalendarSupport === SystemCalendar::PUBLIC_CALENDAR_ALIAS) {
            $this->systemCalendarSupport = [
                SystemCalendar::PUBLIC_CALENDAR_ALIAS => true,
            ];
        }
    }
}
