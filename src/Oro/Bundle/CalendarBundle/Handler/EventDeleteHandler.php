<?php

namespace Oro\Bundle\CalendarBundle\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler as SoapDeleteHandler;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;

class EventDeleteHandler extends SoapDeleteHandler
{
    /** @var SystemCalendarConfig */
    protected $calendarConfig;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SystemCalendarConfig $calendarConfig
     *
     * @return self
     */
    public function setCalendarConfig(SystemCalendarConfig $calendarConfig)
    {
        $this->calendarConfig = $calendarConfig;

        return $this;
    }

    /**
     * @param SecurityFacade $securityFacade
     *
     * @return self
     */
    public function setSecurityFacade(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function checkPermissions($entity, ObjectManager $em)
    {
        if ($entity->getSystemCalendar()) {
            if ($entity->getSystemCalendar()->isPublic()
                && !$this->calendarConfig->isPublicCalendarEnabled()) {
                throw new ForbiddenException('Public Calendars does not supported.');
            }

            if (!$entity->getSystemCalendar()->isPublic()
                && !$this->calendarConfig->isSystemCalendarEnabled()) {
                throw new ForbiddenException('System Calendars does not supported.');
            }

            if ($entity->getSystemCalendar()->isPublic()
                && !$this->securityFacade->isGranted('oro_public_calendar_event_management')) {
                throw new ForbiddenException('Access denied to public calendar events management.');
            } elseif (!$this->securityFacade->isGranted('oro_system_calendar_event_management')) {
                throw new ForbiddenException('Access denied to system calendar events management.');
            }
        } else {
            parent::checkPermissions($entity, $em);
        }
    }
}
