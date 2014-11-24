<?php

namespace Oro\Bundle\CalendarBundle\Provider;

class UserCalendarEventNormalizer extends AbstractCalendarEventNormalizer
{
    /**
     * {@inheritdoc}
     */
    protected function applyPermission(&$resultItem, $calendarId)
    {
        $resultItem['editable']  =
            ($resultItem['calendar'] === $calendarId)
            && $this->securityFacade->isGranted('oro_calendar_event_update');
        $resultItem['removable'] =
            ($resultItem['calendar'] === $calendarId)
            && $this->securityFacade->isGranted('oro_calendar_event_delete');
    }
}
