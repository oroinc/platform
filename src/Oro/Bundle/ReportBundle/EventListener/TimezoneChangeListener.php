<?php

namespace Oro\Bundle\ReportBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ReportBundle\Entity\Manager\CalendarDateManager;

/**
 * Updates calendar dates when the time zone is changed
 */
class TimezoneChangeListener
{
    /** @var CalendarDateManager */
    private $calendarDateManager;

    public function __construct(CalendarDateManager $calendarDateManager)
    {
        $this->calendarDateManager = $calendarDateManager;
    }

    public function onConfigUpdate(ConfigUpdateEvent $event)
    {
        if (!$event->isChanged('oro_locale.timezone')) {
            return;
        }

        $this->calendarDateManager->handleCalendarDates(true);
    }
}
