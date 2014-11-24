<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager;

class UserCalendarEventNormalizer extends AbstractCalendarEventNormalizer
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
        $resultItem['editable']  =
            ($resultItem['calendar'] === $calendarId)
            && $this->securityFacade->isGranted('oro_calendar_event_update');
        $resultItem['removable'] =
            ($resultItem['calendar'] === $calendarId)
            && $this->securityFacade->isGranted('oro_calendar_event_delete');
    }
}
