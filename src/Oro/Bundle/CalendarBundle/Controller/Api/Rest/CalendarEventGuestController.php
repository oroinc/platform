<?php

namespace Oro\Bundle\CalendarBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\Get;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

/**
 * @RouteResource("calendarevents_guest")
 * @NamePrefix("oro_api_")
 */
class CalendarEventGuestController extends RestGetController
{
    /**
     * Get calendar event guests info.
     *
     * @ApiDoc(
     *      description="Get calendar event guests info",
     *      resource=true
     * )
     *
     * @QueryParam(
     *      name="page",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Page number, starting from 1. Defaults to 1."
     * )
     * @QueryParam(
     *      name="limit",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Number of items per page. Defaults to all."
     * )
     *
     * @AclAncestor("oro_calendar_event_view")
     *
     * @param int $id The id of the calendar event entity.
     *
     * @return Response
     */
    public function cgetAction($id)
    {
        $page  = (int)$this->getRequest()->get('page', 1);
        $limit = (int)$this->getRequest()->get('limit');

        return $this->handleGetListRequest($page, $limit, ['parent' => $id]);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_calendar.calendar_event_guest.manager.api');
    }
}
