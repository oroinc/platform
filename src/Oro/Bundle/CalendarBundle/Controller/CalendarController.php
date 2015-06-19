<?php

namespace Oro\Bundle\CalendarBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Bundle\CalendarBundle\Provider\CalendarDateTimeConfigProvider;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class CalendarController extends Controller
{
    /**
     * View user's default calendar
     *
     * @Route("/default", name="oro_calendar_view_default")
     * @Template
     * @AclAncestor("oro_calendar_view")
     */
    public function viewDefaultAction()
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var Organization $organization */
        $organization = $this->get('oro_security.security_facade')->getOrganization();

        $em = $this->getDoctrine()->getManager();
        /** @var CalendarRepository $repo */
        $repo     = $em->getRepository('OroCalendarBundle:Calendar');

        $calendar = $repo->findDefaultCalendar($user->getId(), $organization->getId());

        return $this->viewAction($calendar);
    }

    /**
     * View calendar
     *
     * @Route("/view/{id}", name="oro_calendar_view", requirements={"id"="\d+"})
     *
     * @Template
     * @Acl(
     *      id="oro_calendar_view",
     *      type="entity",
     *      class="OroCalendarBundle:Calendar",
     *      permission="VIEW",
     *      group_name=""
     * )
     */
    public function viewAction(Calendar $calendar)
    {
        /** @var SecurityFacade $securityFacade */
        $securityFacade = $this->get('oro_security.security_facade');
        /** @var CalendarDateTimeConfigProvider $calendarConfigProvider */
        $calendarConfigProvider = $this->get('oro_calendar.provider.calendar_config');

        $dateRange = $calendarConfigProvider->getDateRange();

        $result = array(
            'event_form' => $this->get('oro_calendar.calendar_event.form.template')->createView(),
            'user_select_form' => $this->get('form.factory')
                ->createNamed(
                    'new_calendar',
                    'oro_user_select',
                    null,
                    array(
                        'autocomplete_alias' => 'user_calendars',

                        'configs' => array(
                            'entity_id'               => $calendar->getId(),
                            'entity_name'             => 'OroCalendarBundle:Calendar',
                            'excludeCurrent'          => true,
                            'component'               => 'acl-user-autocomplete',
                            'permission'              => 'VIEW',
                            'placeholder'             => 'oro.calendar.form.choose_user_to_add_calendar',
                            'result_template_twig'    => 'OroCalendarBundle:Calendar:Autocomplete/result.html.twig',
                            'selection_template_twig' => 'OroCalendarBundle:Calendar:Autocomplete/selection.html.twig',
                        ),

                        'grid_name' => 'users-calendar-select-grid-exclude-owner',
                        'random_id' => false,
                        'required'  => true,
                    )
                )
                ->createView(),
            'entity' => $calendar,
            'calendar' => array(
                'selectable' => $securityFacade->isGranted('oro_calendar_event_create'),
                'editable' => $securityFacade->isGranted('oro_calendar_event_update'),
                'removable' => $securityFacade->isGranted('oro_calendar_event_delete'),
                'timezoneOffset' => $calendarConfigProvider->getTimezoneOffset()
            ),
            'startDate' => $dateRange['startDate'],
            'endDate' => $dateRange['endDate'],
        );

        return $result;
    }
}
