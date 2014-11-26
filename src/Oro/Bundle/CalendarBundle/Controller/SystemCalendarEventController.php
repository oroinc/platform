<?php

namespace Oro\Bundle\CalendarBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

class SystemCalendarEventController extends Controller
{
    /**
     * @Route("/{id}/event", name="oro_system_calendar_event_index", requirements={"id"="\d+"})
     * @Template
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

    /**
     * @Route("/event/view/{id}", name="oro_system_calendar_event_view", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_calendar_event_view")
     */
    public function viewAction(CalendarEvent $entity)
    {
        //Check: is event from system calendar
        if (!$entity->getSystemCalendar()) {
            throw new NotFoundHttpException();
        }

        //Check: does user have permission to view system calendar
        if (!$entity->getSystemCalendar()->isPublic()
            && !$this->get('oro_security.security_facade')->isGranted('VIEW', $entity->getSystemCalendar())) {
            throw new AccessDeniedException('Access denied to foreign system calendar events');
        }

        return [
            'entity' => $entity,
        ];
    }
}
