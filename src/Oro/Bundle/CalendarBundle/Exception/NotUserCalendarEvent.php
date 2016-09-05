<?php

namespace Oro\Bundle\CalendarBundle\Exception;

class NotUserCalendarEvent extends \LogicException
{
    /**
     * NotUserCalendarEvent constructor.
     *
     * @param string $id
     */
    public function __construct($id)
    {
        $this->message  = sprintf('Only user\'s calendar events can have reminders. Event Id: %d.', $id);

        parent::__construct($this->getMessage(), $this->getCode(), $this);
    }
}
