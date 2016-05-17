<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent as BaseCalendarEvent;

/**
 * Contains auto generated methods
 */
class CalendarEvent extends BaseCalendarEvent
{
    protected $origin;

    public function getOrigin()
    {
        return $this->origin;
    }

    public function setOrigin($origin)
    {
        $this->origin = $origin;
    }
}
