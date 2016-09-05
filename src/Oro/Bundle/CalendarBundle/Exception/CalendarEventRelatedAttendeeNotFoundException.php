<?php

namespace Oro\Bundle\CalendarBundle\Exception;

class CalendarEventRelatedAttendeeNotFoundException extends \Exception
{
    /** @var string */
    protected $message = 'Calendar event does not have relatedAttendee';
}
