<?php

namespace Oro\Bundle\CalendarBundle\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;

class CalendarEventDeleteHandler extends DeleteHandler
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
        /** @var SystemCalendar|null $calendar */
        $calendar = $entity->getSystemCalendar();
        if ($calendar) {
            if ($calendar->isPublic()) {
                if (!$this->calendarConfig->isPublicCalendarEnabled()) {
                    throw new ForbiddenException('Public calendars are disabled.');
                } elseif (!$this->securityFacade->isGranted('oro_public_calendar_event_management')) {
                    throw new ForbiddenException('Access denied.');
                }
            } else {
                if (!$this->calendarConfig->isSystemCalendarEnabled()) {
                    throw new ForbiddenException('System calendars are disabled.');
                } elseif (!$this->securityFacade->isGranted('oro_system_calendar_event_management')) {
                    throw new ForbiddenException('Access denied.');
                }
            }
        } else {
            parent::checkPermissions($entity, $em);
        }
    }
}
