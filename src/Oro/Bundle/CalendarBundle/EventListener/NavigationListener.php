<?php

namespace Oro\Bundle\CalendarBundle\EventListener;

use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;

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
            $calendarListItem = MenuUpdateUtils::findMenuItem($event->getMenu(), 'oro_system_calendar_list');
            if ($calendarListItem !== null) {
                $calendarListItem->setDisplay(false);
            }
        }
    }
}
