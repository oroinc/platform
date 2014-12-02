<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager;

class SystemCalendarEventNormalizer extends AbstractCalendarEventNormalizer
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SecurityFacade    $securityFacade
     * @param ReminderManager   $reminderManager
     */
    public function __construct(
        SecurityFacade $securityFacade,
        ReminderManager $reminderManager
    ) {
        parent::__construct($reminderManager);
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyPermission(&$resultItem, $calendarId)
    {
        $resultItem['editable']  = false;
        $resultItem['removable'] = false;

        if ($this->securityFacade->isGranted('oro_system_calendar_event_management')) {
            $resultItem['editable']  = true;
            $resultItem['removable'] = true;
        }
    }
}
