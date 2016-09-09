<?php

namespace Oro\Bundle\CalendarBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class SystemCalendarController extends Controller
{
    /**
     * @Route(name="oro_system_calendar_index")
     * @Template
     */
    public function indexAction()
    {
        $calendarConfig = $this->getCalendarConfig();
        if (!$calendarConfig->isPublicCalendarEnabled() && !$calendarConfig->isSystemCalendarEnabled()) {
            throw $this->createNotFoundException('Both Public and System calendars are disabled.');
        }

        return [
            'entity_class' => $this->container->getParameter('oro_calendar.system_calendar.entity.class')
        ];
    }

    /**
     * @Route("/view/{id}", name="oro_system_calendar_view", requirements={"id"="\d+"})
     * @Template
     */
    public function viewAction(SystemCalendar $entity)
    {
        $this->checkPermissionByConfig($entity);

        return [
            'entity'      => $entity,
            'editable'    => $entity->isPublic()
                ? $this->getSecurityFacade()->isGranted('oro_public_calendar_management')
                : $this->getSecurityFacade()->isGranted('oro_system_calendar_update'),
            'removable'   => $entity->isPublic()
                ? $this->getSecurityFacade()->isGranted('oro_public_calendar_management')
                : $this->getSecurityFacade()->isGranted('oro_system_calendar_delete'),
            'canAddEvent' => $entity->isPublic()
                ? $this->getSecurityFacade()->isGranted('oro_public_calendar_event_management')
                : $this->getSecurityFacade()->isGranted('oro_system_calendar_event_management'),
            'showScope'   =>
                $this->getCalendarConfig()->isPublicCalendarEnabled()
                && $this->getCalendarConfig()->isSystemCalendarEnabled()
        ];
    }
    /**
     * @Route("/create", name="oro_system_calendar_create")
     * @Template("OroCalendarBundle:SystemCalendar:update.html.twig")
     */
    public function createAction()
    {
        $calendarConfig = $this->getCalendarConfig();
        if (!$calendarConfig->isPublicCalendarEnabled() && !$calendarConfig->isSystemCalendarEnabled()) {
            throw $this->createNotFoundException('Both Public and System calendars are disabled.');
        }

        $securityFacade = $this->getSecurityFacade();
        $isGranted      = $securityFacade->isGranted('oro_public_calendar_management')
            || $securityFacade->isGranted('oro_system_calendar_create');
        if (!$isGranted) {
            throw new AccessDeniedException();
        }

        return $this->update(
            new SystemCalendar(),
            $this->get('router')->generate('oro_system_calendar_create')
        );
    }

    /**
     * @Route("/update/{id}", name="oro_system_calendar_update", requirements={"id"="\d+"})
     * @Template("OroCalendarBundle:SystemCalendar:update.html.twig")
     */
    public function updateAction(SystemCalendar $entity)
    {
        $this->checkPermissionByConfig($entity);

        $isGranted = $entity->isPublic()
            ? $this->getSecurityFacade()->isGranted('oro_public_calendar_management')
            : $this->getSecurityFacade()->isGranted('oro_system_calendar_update');
        if (!$isGranted) {
            throw new AccessDeniedException();
        }

        return $this->update(
            $entity,
            $this->get('router')->generate('oro_system_calendar_update', ['id' => $entity->getId()])
        );
    }

    /**
     * @Route("/widget/events/{id}", name="oro_system_calendar_widget_events", requirements={"id"="\d+"})
     * @Template
     */
    public function eventsAction(SystemCalendar $entity)
    {
        $this->checkPermissionByConfig($entity);

        if (!$entity->isPublic() && !$this->getSecurityFacade()->isGranted('VIEW', $entity)) {
            // an user must have permissions to view system calendar
            throw new AccessDeniedException();
        }

        return [
            'entity' => $entity
        ];
    }

    /**
     * @param SystemCalendar $entity
     * @param string         $formAction
     *
     * @return array
     */
    protected function update(SystemCalendar $entity, $formAction)
    {
        $saved = false;

        if ($this->get('oro_calendar.system_calendar.form.handler')->process($entity)) {
            if (!$this->getRequest()->get('_widgetContainer')) {
                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('oro.calendar.controller.systemcalendar.saved.message')
                );

                return $this->get('oro_ui.router')->redirect($entity);
            }
            $saved = true;
        }

        return array(
            'entity'     => $entity,
            'saved'      => $saved,
            'form'       => $this->get('oro_calendar.system_calendar.form.handler')->getForm()->createView(),
            'formAction' => $formAction
        );
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
