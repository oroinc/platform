<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager;

class UserCalendarEventNormalizer extends AbstractCalendarEventNormalizer
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param ReminderManager $reminderManager
     * @param SecurityFacade  $securityFacade
     */
    public function __construct(ReminderManager $reminderManager, SecurityFacade $securityFacade)
    {
        parent::__construct($reminderManager);
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyPermissions(&$item, $calendarId)
    {
        $item['editable']  =
            ($item['calendar'] === $calendarId)
            && $this->securityFacade->isGranted('oro_calendar_event_update');
        $item['removable'] =
            ($item['calendar'] === $calendarId)
            && $this->securityFacade->isGranted('oro_calendar_event_delete');
    }
}
