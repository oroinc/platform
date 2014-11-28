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
        return [
            'entity_class' => $this->container->getParameter('oro_calendar.system_calendar.entity.class')
        ];
    }

    /**
     * @Route("/view/{id}", name="oro_system_calendar_view", requirements={"id"="\d+"})
     * @Acl(
     *      id="oro_system_calendar_view",
     *      type="entity",
     *      class="OroCalendarBundle:SystemCalendar",
     *      permission="VIEW",
     *      group_name=""
     * )
     */
    public function viewAction(SystemCalendar $entity)
    {

    }

    /**
     * @Route("/create", name="oro_system_calendar_create")
     * @Acl(
     *      id="oro_system_calendar_create",
     *      type="entity",
     *      class="OroCalendarBundle:SystemCalendar",
     *      permission="CREATE",
     *      group_name=""
     * )
     */
    public function createAction()
    {

    }

    /**
     * @Route("/update/{id}", name="oro_system_calendar_update", requirements={"id"="\d+"})
     * @Acl(
     *      id="oro_system_calendar_update",
     *      type="entity",
     *      class="OroCalendarBundle:SystemCalendar",
     *      permission="EDIT",
     *      group_name=""
     * )
     */
    public function updateAction(SystemCalendar $entity)
    {

    }
}
