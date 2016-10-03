<?php

namespace Oro\Bundle\CalendarBundle\EventListener;

use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Bundle\NavigationBundle\Helper\MenuUpdateHelper;

class NavigationListener
{
    /** @var MenuUpdateHelper */
    protected $menuUpdateHelper;

    /** @var SystemCalendarConfig */
    protected $calendarConfig;

    /**
     * @param MenuUpdateHelper $menuUpdateHelper
     * @param SystemCalendarConfig $calendarConfig
     */
    public function __construct(MenuUpdateHelper $menuUpdateHelper, SystemCalendarConfig $calendarConfig)
    {
        $this->menuUpdateHelper = $menuUpdateHelper;
        $this->calendarConfig = $calendarConfig;
    }

    /**
     * @param ConfigureMenuEvent $event
     */
    public function onNavigationConfigure(ConfigureMenuEvent $event)
    {
        if (!$this->calendarConfig->isPublicCalendarEnabled() && !$this->calendarConfig->isSystemCalendarEnabled()) {
            $calendarListItem = $this->menuUpdateHelper->findMenuItem($event->getMenu(), 'oro_system_calendar_list');
            if ($calendarListItem !== null) {
                $calendarListItem->setDisplay(false);
            }
        }
    }
}
