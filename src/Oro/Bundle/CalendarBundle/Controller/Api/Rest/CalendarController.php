<?php

namespace Oro\Bundle\CalendarBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\UserBundle\Entity\User;

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
     * @Get("/calendars/default")
     *
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
        /** @var User $user */
        $user = $this->getUser();

        /** @var Organization $organization */
        $organization = $this->get('oro_security.security_facade')->getOrganization();

        $em = $this->getDoctrine()->getManager();
        /** @var CalendarRepository $repo */
        $repo = $em->getRepository('OroCalendarBundle:Calendar');

        $calendar = $repo->findDefaultCalendar($user->getId(), $organization->getId());

        $result = array(
            'calendar'      => $calendar->getId(),
            'owner'         => $calendar->getOwner()->getId(),
            'calendarName'  => $calendar->getName(),
        );

        if (!$result['calendarName']) {
            $result['calendarName'] = $this->get('oro_entity.entity_name_resolver')->getName($calendar->getOwner());
        }

        return new Response(json_encode($result), Codes::HTTP_OK);
    }
}
