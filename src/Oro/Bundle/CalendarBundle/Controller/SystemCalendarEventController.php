<?php

namespace Oro\Bundle\CalendarBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class SystemCalendarEventController extends Controller
{
    /**
     * @Route("/event/view/{id}", name="oro_system_calendar_event_view", requirements={"id"="\d+"})
     * @Template
     */
    public function viewAction(CalendarEvent $entity)
    {
        $calendar = $entity->getSystemCalendar();
        if (!$calendar) {
            // an event must belong to system calendar
            throw $this->createNotFoundException('Not system calendar event.');
        }

        $this->checkPermissionByConfig($calendar);

        $securityFacade = $this->getSecurityFacade();
        if (!$calendar->isPublic() && !$securityFacade->isGranted('VIEW', $calendar)) {
            // an user must have permissions to view system calendar
            throw new AccessDeniedException();
        }

        $isEventManagementGranted = $calendar->isPublic()
            ? $securityFacade->isGranted('oro_public_calendar_event_management')
            : $securityFacade->isGranted('oro_system_calendar_event_management');

        return [
            'entity'    => $entity,
            'editable'  => $isEventManagementGranted,
            'removable' => $isEventManagementGranted
        ];
    }

    /**
     * @Route("/{id}/event/create", name="oro_system_calendar_event_create", requirements={"id"="\d+"})
     * @Template("OroCalendarBundle:SystemCalendarEvent:update.html.twig")
     */
    public function createAction(SystemCalendar $calendar)
    {
        $this->checkPermissionByConfig($calendar);

        $securityFacade = $this->getSecurityFacade();
        $isGranted = $calendar->isPublic()
            ? $securityFacade->isGranted('oro_public_calendar_event_management')
            : $securityFacade->isGranted('oro_system_calendar_event_management');
        if (!$isGranted) {
            throw new AccessDeniedException();
        }

        $entity = new CalendarEvent();

        $startTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $endTime   = new \DateTime('now', new \DateTimeZone('UTC'));
        $endTime->add(new \DateInterval('PT1H'));
        $entity->setStart($startTime);
        $entity->setEnd($endTime);
        $entity->setSystemCalendar($calendar);

        return $this->update(
            $entity,
            $this->get('router')->generate('oro_system_calendar_event_create', ['id' => $calendar->getId()])
        );
    }

    /**
     * @Route("/event/update/{id}", name="oro_system_calendar_event_update", requirements={"id"="\d+"})
     * @Template
     */
    public function updateAction(CalendarEvent $entity)
    {
        $calendar = $entity->getSystemCalendar();
        if (!$calendar) {
            // an event must belong to system calendar
            throw $this->createNotFoundException('Not system calendar event.');
        }

        $this->checkPermissionByConfig($calendar);

        $securityFacade = $this->getSecurityFacade();
        if (!$calendar->isPublic() && !$securityFacade->isGranted('VIEW', $calendar)) {
            // an user must have permissions to view system calendar
            throw new AccessDeniedException();
        }

        $isGranted = $calendar->isPublic()
            ? $securityFacade->isGranted('oro_public_calendar_event_management')
            : $securityFacade->isGranted('oro_system_calendar_event_management');
        if (!$isGranted) {
            throw new AccessDeniedException();
        }

        return $this->update(
            $entity,
            $this->get('router')->generate('oro_system_calendar_event_update', ['id' => $entity->getId()])
        );
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

                return $this->get('oro_ui.router')->redirect($entity);
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

    /**
     * @param SystemCalendar $entity
     *
     * @throws NotFoundHttpException
     */
    protected function checkPermissionByConfig(SystemCalendar $entity)
    {
        if ($entity->isPublic()) {
            if (!$this->getCalendarConfig()->isPublicCalendarEnabled()) {
                throw $this->createNotFoundException('Public calendars are disabled.');
            }
        } else {
            if (!$this->getCalendarConfig()->isSystemCalendarEnabled()) {
                throw $this->createNotFoundException('System calendars are disabled.');
            }
        }
    }

    /**
     * @return SystemCalendarConfig
     */
    protected function getCalendarConfig()
    {
        return $this->get('oro_calendar.system_calendar_config');
    }

    /**
     * @return SecurityFacade
     */
    protected function getSecurityFacade()
    {
        return $this->get('oro_security.security_facade');
    }
}
