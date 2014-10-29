<?php

namespace Oro\Bundle\CalendarBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\Rest\Util\Codes;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("calendar")
 * @NamePrefix("oro_api_")
 */
class CalendarController extends FOSRestController
{
    /**
     * Get Default Calendar of User
     *
     * @QueryParam(
     *      name="user", requirements="\d+", nullable=false, strict=true,
     *      description="User id.")
     * @QueryParam(
     *      name="organization", requirements="\d+", nullable=false, strict=true,
     *      description="Organization id.")
     * @ApiDoc(
     *      description="Get default calendar of user",
     *      resource=true
     * )
     * @AclAncestor("oro_calendar_view")
     *
     * @return Response
     */
    public function getDefaultAction()
    {
        $user = (int)$this->getRequest()->get('user');
        $organization = (int)$this->getRequest()->get('organization');

        $em = $this->getDoctrine()->getManager();
        /** @var CalendarRepository $repo */
        $repo = $em->getRepository('OroCalendarBundle:Calendar');
        /** @var Calendar $calendar */
        $calendar = $repo->findDefaultCalendar($user, $organization);

        if (!$calendar) {
            return new Response('', Codes::HTTP_NOT_FOUND);
        }

        $result = array(
            'calendar'      => $calendar->getId(),
            'organization'  => $calendar->getOrganization()->getId(),
            'owner'         => $calendar->getOwner()->getId(),
            'calendarName'  => $calendar->getName(),
        );

        if (!$result['calendarName']) {
            $result['calendarName'] = $this->get('oro_locale.formatter.name')->format($calendar->getOwner());
        }

        return new Response(json_encode($result), Codes::HTTP_OK);
    }
}
