<?php

namespace Oro\Bundle\CalendarBundle\EventListener;

use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;

class NavigationListener
{
    /** @var SystemCalendarConfig */
    protected $calendarConfig;

    /**
     * @param SystemCalendarConfig $calendarConfig
     */
    public function __construct(SystemCalendarConfig $calendarConfig)
    {
        $this->calendarConfig = $calendarConfig;
    }

    /**
     * @param ConfigureMenuEvent $event
     */
    public function onNavigationConfigure(ConfigureMenuEvent $event)
    {
        if (!$this->calendarConfig->isPublicCalendarEnabled() && !$this->calendarConfig->isSystemCalendarEnabled()) {
            $event->getMenu()
                ->getChild('system_tab')
                ->getChild('oro_system_calendar_list')
                ->setDisplay(false);
        }
    }
}
