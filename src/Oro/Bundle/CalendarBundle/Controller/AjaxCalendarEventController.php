<?php

namespace Oro\Bundle\CalendarBundle\Controller;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

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
     *      requirements={"id"="\d+"}, defaults={"status"="tentatively_accepted"})
     * @Route("/decline/{id}",
     *      name="oro_calendar_event_declined",
     *      requirements={"id"="\d+"}, defaults={"status"="declined"})
     * @param CalendarEvent $entity
     * @param string $status
     * @return JsonResponse
     */
    public function changeStatus(CalendarEvent $entity, $status)
    {
        $em = $this->getDoctrine()->getManager();
        $entity->setInvitationStatus($status);
        $em->flush($entity);
        $this->get('oro_calendar.send_processor.email')->sendRespondNotification($entity);

        return new JsonResponse(['successful' => true]);
    }
}
