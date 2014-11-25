<?php

namespace Oro\Bundle\CalendarBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
     * @TODO: Replace to AclAncestor after implemented BAP-5989
     * @Acl(
     *      id="oro_system_calendar_view",
     *      type="entity",
     *      class="OroCalendarBundle:SystemCalendar",
     *      permission="VIEW",
     *      group_name=""
     * )
     */
    public function indexAction(SystemCalendar $entity)
    {
        if (!$entity->isPublic() && !$this->get('oro_security.security_facade')->isGranted('VIEW', $entity)) {
            throw new AccessDeniedException('Access denied to foreign system calendar events');
        }

        return [
            'params'        => ['calendarId' => $entity->getId()],
            'gridName'      => $entity->isPublic() ? 'public-system-calendar-event-grid' : 'system-calendar-event-grid',
            'entity_class'  => $this->container->getParameter('oro_calendar.calendar_event.entity.class')
        ];
    }
}
