<?php

namespace Oro\Bundle\CalendarBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

class SystemCalendarController extends Controller
{
    /**
     * @Route(name="oro_system_calendar_index")
     * @Template
     */
    public function indexAction()
    {
        if (!$this->get('oro_calendar.system_calendar.config_helper')->isSomeSystemCalendarSupported()) {
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
        if ($entity->isPublic()
            && !$this->get('oro_calendar.system_calendar.config_helper')->isPublicCalendarSupported()) {
            throw $this->createNotFoundException('Public Calendars does not supported.');
        }

        if (!$entity->isPublic()
            && !$this->get('oro_calendar.system_calendar.config_helper')->isSystemCalendarSupported()) {
            throw $this->createNotFoundException('System Calendars does not supported.');
        }
    }

    /**
     * @Route("/create", name="oro_system_calendar_create")
     */
    public function createAction()
    {
        if (!$this->get('oro_calendar.system_calendar.config_helper')->isSomeSystemCalendarSupported()) {
            throw $this->createNotFoundException('System and Public Calendars does not supported.');
        }
        //@TODO: Added verification system and public calendars supported separately(after BAP-5991 will be implemented)
    }

    /**
     * @Route("/update/{id}", name="oro_system_calendar_update", requirements={"id"="\d+"})
     */
    public function updateAction(SystemCalendar $entity)
    {
        if ($entity->isPublic()
            && !$this->get('oro_calendar.system_calendar.config_helper')->isPublicCalendarSupported()) {
            throw $this->createNotFoundException('Public Calendars does not supported.');
        }

        if (!$entity->isPublic()
            && !$this->get('oro_calendar.system_calendar.config_helper')->isSystemCalendarSupported()) {
            throw $this->createNotFoundException('System Calendars does not supported.');
        }
        //@TODO: Added verification system and public calendars supported separately(after BAP-5991 will be implemented)
    }
}
