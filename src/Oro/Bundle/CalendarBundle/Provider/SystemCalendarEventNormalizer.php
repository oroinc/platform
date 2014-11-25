<?php

namespace Oro\Bundle\CalendarBundle\Provider;

class SystemCalendarEventNormalizer extends AbstractCalendarEventNormalizer
{
    /**
     * {@inheritdoc}
     */
    protected function applyPermission(&$resultItem, $calendarId)
    {
        //@TODO: it must be override in BAP-5998
        $resultItem['editable']  = false;
        $resultItem['removable'] = false;
    }
}
