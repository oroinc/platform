<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class SystemCalendarEventNormalizer extends AbstractCalendarEventNormalizer
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param ReminderManager $reminderManager
     * @param SecurityFacade  $securityFacade
     * @param DoctrineHelper  $doctrineHelper
     */
    public function __construct(
        ReminderManager $reminderManager,
        SecurityFacade $securityFacade,
        DoctrineHelper $doctrineHelper
    ) {
        parent::__construct($reminderManager, $doctrineHelper);
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyPermissions(&$item, $calendarId)
    {
        if (!$this->securityFacade->isGranted('oro_system_calendar_event_management')) {
            $item['editable']  = false;
            $item['removable'] = false;
        }
    }
}
