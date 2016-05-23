<?php

namespace Oro\Bundle\CalendarBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Exception\RelatedAttendeeNotFoundException;
use Oro\Bundle\CalendarBundle\Exception\StatusNotFoundException;

/**
 * @Route("/event/ajax")
 */
class AjaxCalendarEventController extends Controller
{
    /**
     * @Route("/accept/{id}",
     *      name="oro_calendar_event_accepted",
     *      requirements={"id"="\d+"}, defaults={"status"="accepted"})
     * @Route("/tentatively/{id}",
     *      name="oro_calendar_event_tentatively_accepted",
     *      requirements={"id"="\d+"}, defaults={"status"="tentative"})
     * @Route("/tentatively/{id}",
     *      name="oro_calendar_event_tentative",
     *      requirements={"id"="\d+"}, defaults={"status"="tentative"})
     * @Route("/decline/{id}",
     *      name="oro_calendar_event_declined",
     *      requirements={"id"="\d+"}, defaults={"status"="declined"})
     *
     * @param CalendarEvent $entity
     * @param string        $status
     *
     * @return JsonResponse
     */
    public function changeStatus(CalendarEvent $entity, $status)
    {
        try {
            $this->get('oro_calendar.calendar_event_manager')->changeStatus($entity, $status);
        } catch (RelatedAttendeeNotFoundException $ex) {
            return new JsonResponse([
                'successfull' => false,
                'message' => $ex->getMessage(),
            ]);
        } catch (StatusNotFoundException $ex) {
            return new JsonResponse([
                'successfull' => false,
                'message' => $ex->getMessage(),
            ]);
        }

        $this->getDoctrine()
            ->getManagerForClass('Oro\Bundle\CalendarBundle\Entity\CalendarEvent')
            ->flush();

        $this->get('oro_calendar.send_processor.email')->sendRespondNotification($entity);

        return new JsonResponse(['successful' => true]);
    }
}
