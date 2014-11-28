<?php

namespace Oro\Bundle\CalendarBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfigHelper;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

class SystemCalendarController extends Controller
{
    /**
     * @Route(name="oro_system_calendar_index")
     * @Template
     */
    public function indexAction()
    {
        if (!$this->getCalendarConfigHelper()->isSomeSystemCalendarSupported()) {
            throw $this->createNotFoundException('System and Public Calendars does not supported.');
        }

        return [
            'entity_class' => $this->container->getParameter('oro_calendar.system_calendar.entity.class')
        ];
    }

    /**
     * @Route("/view/{id}", name="oro_system_calendar_view", requirements={"id"="\d+"})
     */
    public function viewAction(SystemCalendar $entity)
    {
        $this->checkPermissionByConfig($entity);
    }

    /**
     * @Route("/create", name="oro_system_calendar_create")
     */
    public function createAction()
    {
        if (!$this->getCalendarConfigHelper()->isSomeSystemCalendarSupported()) {
            throw $this->createNotFoundException('System and Public Calendars does not supported.');
        }
        //@TODO: Added verification system and public calendars supported separately(after BAP-5991 will be implemented)
    }

    /**
     * @Route("/update/{id}", name="oro_system_calendar_update", requirements={"id"="\d+"})
     */
    public function updateAction(SystemCalendar $entity)
    {
        $this->checkPermissionByConfig($entity);
        //@TODO: Added verification system and public calendars supported separately(after BAP-5991 will be implemented)
    }

    /**
     * @param SystemCalendar $entity
     *
     * @throws NotFoundHttpException
     */
    protected function checkPermissionByConfig(SystemCalendar $entity)
    {
        if ($entity->isPublic()
            && !$this->getCalendarConfigHelper()->isPublicCalendarSupported()) {
            throw $this->createNotFoundException('Public Calendars does not supported.');
        }

        if (!$entity->isPublic()
            && !$this->getCalendarConfigHelper()->isSystemCalendarSupported()) {
            throw $this->createNotFoundException('System Calendars does not supported.');
        }
    }

    /**
     * @return SystemCalendarConfigHelper
     */
    protected function getCalendarConfigHelper()
    {
        return $this->get('oro_calendar.system_calendar.config_helper');
    }
}
