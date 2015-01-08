<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager;

class PublicCalendarEventNormalizer extends AbstractCalendarEventNormalizer
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
        if (!$this->securityFacade->isGranted('oro_public_calendar_event_management')) {
            $item['editable']  = false;
            $item['removable'] = false;
        }
    }
}
