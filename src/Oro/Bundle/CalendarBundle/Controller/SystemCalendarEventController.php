<?php

namespace Oro\Bundle\CalendarBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;

/**
 * @Route("/system")
 */
class SystemCalendarEventController extends Controller
{
    /**
     * @Route("/{id}/event", name="oro_calendar_system_event_index", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_calendar_system_event_view",
     *      type="entity",
     *      class="OroCalendarBundle:SystemCalendar",
     *      permission="VIEW",
     *      group_name=""
     * )
     */
    public function indexAction(SystemCalendar $entity)
    {
        if (!$entity->isPublic() && $entity->getOrganization()
            && $entity->getOrganization()->getId() != $this->get('oro_security.security_facade')->getOrganizationId()) {
            throw new AccessDeniedHttpException('Access denied to foreign system calendar events');
        }

        return [
            'params'        => ['calendarId' => $entity->getId()],
            'gridName'      => $entity->isPublic() ? 'public-system-calendar-event-grid' : 'system-calendar-event-grid',
            'entity_class'  => $this->container->getParameter('oro_calendar.calendar_event.entity.class')
        ];
    }
}
