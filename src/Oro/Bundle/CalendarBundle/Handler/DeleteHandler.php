<?php

namespace Oro\Bundle\CalendarBundle\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler as SoapDeleteHandler;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfigHelper;

class DeleteHandler extends SoapDeleteHandler
{
    /** @var SystemCalendarConfigHelper */
    protected $calendarConfigHelper;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SystemCalendarConfigHelper $calendarConfigHelper
     */
    public function setCalendarConfigHelper(SystemCalendarConfigHelper $calendarConfigHelper)
    {
        $this->calendarConfigHelper = $calendarConfigHelper;
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
        if ($entity->isPublic()
            && !$this->calendarConfigHelper->isPublicCalendarSupported()) {
            throw new ForbiddenException('Public Calendars does not supported.');
        }

        if (!$entity->isPublic()
            && !$this->calendarConfigHelper->isSystemCalendarSupported()) {
            throw new ForbiddenException('System Calendars does not supported.');
        }

        if (!$entity->isPublic() && !$this->securityFacade->isGranted('oro_system_calendar_delete', $entity)) {
            throw new ForbiddenException('Access denied to system calendars from another organization');
        }
    }
}
