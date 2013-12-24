<?php

namespace Oro\Bundle\CalendarBundle\Controller\Dashboard;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\CalendarBundle\Provider\CalendarDateTimeConfigProvider;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class DashboardController extends Controller
{
    /**
     * @Route(
     *      "/my_calendar/{widget}",
     *      name="oro_calendar_dashboard_my_calendar",
     *      requirements={"widget"="[\w_-]+"}
     * )
     * @Template("OroCalendarBundle:Dashboard:myCalendar.html.twig")
     */
    public function myCalendarAction($widget)
    {
        /** @var SecurityFacade $securityFacade */
        $securityFacade = $this->get('oro_security.security_facade');
        /** @var CalendarDateTimeConfigProvider $calendarConfigProvider */
        $calendarConfigProvider = $this->get('oro_calendar.provider.calendar_config');
        /** @var LocaleSettings $localeSettings */
        $localeSettings = $this->get('oro_locale.settings');

        $calendar    = $this->getDoctrine()->getManager()
            ->getRepository('OroCalendarBundle:Calendar')
            ->findByUser($this->getUser()->getId());
        $currentDate = new \DateTime('now', new \DateTimeZone($localeSettings->getTimeZone()));
        $startDate   = clone $currentDate;
        $startDate->setTime(0, 0, 0);
        $endDate = clone $startDate;
        $endDate->add(new \DateInterval('P1D'));
        $firstHour = intval($currentDate->format('G'));
        if (intval($currentDate->format('i')) <= 30 && $firstHour !== 0) {
            $firstHour--;
        }

        $result = array(
            'event_form' => $this->get('oro_calendar.calendar_event.form')->createView(),
            'entity'     => $calendar,
            'calendar'   => array(
                'selectable'     => $securityFacade->isGranted('oro_calendar_event_create'),
                'editable'       => $securityFacade->isGranted('oro_calendar_event_update'),
                'removable'      => $securityFacade->isGranted('oro_calendar_event_delete'),
                'timezoneOffset' => $calendarConfigProvider->getTimezoneOffset()
            ),
            'startDate'  => $startDate,
            'endDate'    => $endDate,
            'firstHour'  => $firstHour
        );
        $result = array_merge(
            $result,
            $this->get('oro_dashboard.manager')->getWidgetAttributesForTwig($widget)
        );

        return $result;
    }
}
