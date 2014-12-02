<?php

namespace Oro\Bundle\CalendarBundle\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;

class SystemCalendarDeleteHandler extends DeleteHandler
{
    /** @var SystemCalendarConfig */
    protected $calendarConfig;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SystemCalendarConfig $calendarConfig
     */
    public function setCalendarConfig(SystemCalendarConfig $calendarConfig)
    {
        $this->calendarConfig = $calendarConfig;
    }

    /**
     * @param SecurityFacade $securityFacade
     */
    public function setSecurityFacade(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    protected function checkPermissions($entity, ObjectManager $em)
    {
        if ($entity->isPublic()) {
            if (!$this->calendarConfig->isPublicCalendarEnabled()) {
                throw new ForbiddenException('Public calendars are disabled.');
            } elseif (!$this->securityFacade->isGranted('oro_public_calendar_management')) {
                throw new ForbiddenException('Access denied.');
            }
        } else {
            if (!$this->calendarConfig->isSystemCalendarEnabled()) {
                throw new ForbiddenException('System calendars are disabled.');
            } elseif (!$this->securityFacade->isGranted('DELETE', $entity)) {
                throw new ForbiddenException('Access denied.');
            }
        }
    }
}
