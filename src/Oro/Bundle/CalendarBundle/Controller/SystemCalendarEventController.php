<?php

namespace Oro\Bundle\CalendarBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

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
            throw new AccessDeniedException('Access denied to system calendar events from another organization');
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
        //is event from system calendar
        if (!$entity->getSystemCalendar()) {
            throw $this->createNotFoundException(sprintf('Not found %d system calendar event', $entity->getId()));
        }

        //does user have permission to view system calendar
        if (!$entity->getSystemCalendar()->isPublic()
            && !$this->get('oro_security.security_facade')->isGranted('VIEW', $entity->getSystemCalendar())) {
            throw new AccessDeniedException('Access denied to system calendar events from another organization');
        }

        return [
            'entity' => $entity,
        ];
    }

    /**
     * @Route("/{id}/event/create", name="oro_system_calendar_event_create", requirements={"id"="\d+"})
     * @Template("OroCalendarBundle:SystemCalendarEvent:update.html.twig")
     * @AclAncestor("oro_calendar_event_create")
     */
    public function createAction(SystemCalendar $systemCalendar)
    {
        //@TODO: Add check permission to create system calendar event
        $entity = new CalendarEvent();

        $startTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $entity->setStart($startTime);
        $entity->setEnd($startTime->add(new \DateInterval('PT1H')));
        $entity->setSystemCalendar($systemCalendar);

        $formAction = $this->get('oro_entity.routing_helper')
            ->generateUrlByRequest(
                'oro_system_calendar_event_create',
                $this->getRequest(),
                ['id' => $systemCalendar->getId()]
            );

        return $this->update($entity, $formAction);
    }

    /**
     * @Route("/update/{id}", name="oro_system_calendar_event_update", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_calendar_event_update")
     */
    public function updateAction(CalendarEvent $entity)
    {
        //@TODO: Add check permission to update system calendar event
        $formAction = $this->get('router')->generate('oro_system_calendar_event_update', ['id' => $entity->getId()]);

        return $this->update($entity, $formAction);
    }

    /**
     * @param CalendarEvent $entity
     * @param string        $formAction
     *
     * @return array
     */
    protected function update(CalendarEvent $entity, $formAction)
    {
        $saved = false;

        if ($this->get('oro_calendar.system_calendar_event.form.handler')->process($entity)) {
            if (!$this->getRequest()->get('_widgetContainer')) {
                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('oro.calendar.controller.event.saved.message')
                );

                return $this->get('oro_ui.router')->redirectAfterSave(
                    ['route' => 'oro_system_calendar_event_update', 'parameters' => ['id' => $entity->getId()]],
                    ['route' => 'oro_system_calendar_event_view', 'parameters' => ['id' => $entity->getId()]]
                );
            }
            $saved = true;
        }

        return [
            'entity'     => $entity,
            'saved'      => $saved,
            'form'       => $this->get('oro_calendar.calendar_event.form.handler')->getForm()->createView(),
            'formAction' => $formAction
        ];
    }
}
