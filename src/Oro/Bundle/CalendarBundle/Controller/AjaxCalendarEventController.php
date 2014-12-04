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
     * @Route("/change-status/{id}/{status}", name="oro_calendar_event_change_status", requirements={"id"="\d+"})
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

        return new JsonResponse(["success" => true]);
    }
}
