<?php

namespace Oro\Bundle\CalendarBundle\EventListener;

use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfigHelper;

class NavigationListener
{
    /** @var SystemCalendarConfigHelper */
    protected $calendarConfigHelper;

    /**
     * @param SystemCalendarConfigHelper $calendarConfigHelper
     */
    public function __construct(
        SystemCalendarConfigHelper $calendarConfigHelper
    ) {
        $this->calendarConfigHelper = $calendarConfigHelper;
    }

    /**
     * @param ConfigureMenuEvent $event
     */
    public function onNavigationConfigure(ConfigureMenuEvent $event)
    {
        if (!$this->calendarConfigHelper->isSomeSystemCalendarSupported()) {
            $menu = $event->getMenu();
            $menu = $menu->getChild('system_tab')->getChild('oro_system_calendar_list');
            $menu->setDisplay(false);
        }
    }
}
