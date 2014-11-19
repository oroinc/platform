<?php

namespace Oro\Bundle\CalendarBundle\Controller\Api\Rest;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\Rest\Util\Codes;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\HttpDateTimeParameterFilter;

/**
 * @RouteResource("calendarevent")
 * @NamePrefix("oro_api_")
 */
class CalendarEventController extends RestController implements ClassResourceInterface
{
    /**
     * Get calendar events.
     *
     * @QueryParam(
     *      name="calendar", requirements="\d+",
     *      nullable=false,
     *      strict=true,
     *      description="Calendar id."
     * )
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
     *      description="Number of items per page. defaults to 10."
     * )
     * @QueryParam(
     *      name="start",
     *      requirements="\d{4}(-\d{2}(-\d{2}([T ]\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|([-+]\d{2}(:?\d{2})?))?)?)?)?",
     *      nullable=true,
     *      strict=true,
     *      description="Start date in RFC 3339. For example: 2009-11-05T13:15:30Z."
     * )
     * @QueryParam(
     *      name="end",
     *      requirements="\d{4}(-\d{2}(-\d{2}([T ]\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|([-+]\d{2}(:?\d{2})?))?)?)?)?",
     *      nullable=true,
     *      strict=true,
     *      description="End date in RFC 3339. For example: 2009-11-05T13:15:30Z."
     * )
     * @QueryParam(
     *      name="subordinate",
     *      requirements="(true)|(false)",
     *      nullable=true,
     *      strict=true,
     *      default="false",
     *      description="Determines whether events from connected calendars should be included or not."
     * )
     * @QueryParam(
     *     name="createdAt",
     *     requirements="\d{4}(-\d{2}(-\d{2}([T ]\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|([-+]\d{2}(:?\d{2})?))?)?)?)?",
     *     nullable=true,
     *     description="Date in RFC 3339 format. For example: 2009-11-05T13:15:30Z, 2008-07-01T22:35:17+08:00"
     * )
     * @QueryParam(
     *     name="updatedAt",
     *     requirements="\d{4}(-\d{2}(-\d{2}([T ]\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|([-+]\d{2}(:?\d{2})?))?)?)?)?",
     *     nullable=true,
     *     description="Date in RFC 3339 format. For example: 2009-11-05T13:15:30Z, 2008-07-01T22:35:17+08:00"
     * )
     * @ApiDoc(
     *      description="Get calendar events",
     *      resource=true
     * )
     * @AclAncestor("oro_calendar_event_view")
     *
     * @return Response
     */
    public function cgetAction()
    {
        $calendarId  = (int)$this->getRequest()->get('calendar');
        $subordinate = (true == $this->getRequest()->get('subordinate'));

        $qb = null;
        if ($this->getRequest()->get('start') && $this->getRequest()->get('end')) {
            $result = $this->get('oro_calendar.calendar_manager')->getCalendarEvents(
                $this->getUser()->getId(),
                $calendarId,
                new \DateTime($this->getRequest()->get('start')),
                new \DateTime($this->getRequest()->get('end')),
                $subordinate
            );
        } elseif ($this->getRequest()->get('page') && $this->getRequest()->get('limit')) {
            $dateParamFilter  = new HttpDateTimeParameterFilter();
            $filterParameters = ['createdAt' => $dateParamFilter, 'updatedAt' => $dateParamFilter];
            $filterCriteria   = $this->getFilterCriteria(['createdAt', 'updatedAt'], $filterParameters);

            /** @var CalendarEventRepository $repo */
            $repo  = $this->getManager()->getRepository();
            $qb    = $repo->getEventListQueryBuilder($calendarId, $subordinate, $filterCriteria);
            $page  = (int)$this->getRequest()->get('page', 1);
            $limit = (int)$this->getRequest()->get('limit', self::ITEMS_PER_PAGE);
            $qb->setMaxResults($limit)
                ->setFirstResult($page > 0 ? ($page - 1) * $limit : 0);

            $result = $this->get('oro_calendar.calendar_event.normalizer')->getCalendarEvents(
                $calendarId,
                $qb
            );

            return $this->buildResponse($result, self::ACTION_LIST, ['result' => $result, 'query' => $qb]);
        } else {
            throw new BadRequestHttpException(
                'Time interval ("start" and "end") or paging ("page" and "limit") parameters should be specified.'
            );
        }

        return new Response(json_encode($result), Codes::HTTP_OK);
    }

    /**
     * Update calendar event.
     *
     * @param int $id Calendar event id
     *
     * @ApiDoc(
     *      description="Update calendar event",
     *      resource=true
     * )
     * @AclAncestor("oro_calendar_event_update")
     *
     * @return Response
     */
    public function putAction($id)
    {
        return $this->handleUpdateRequest($id);
    }

    /**
     * Create new calendar event.
     *
     * @Post("calendarevents", name="oro_api_post_calendarevent")
     * @ApiDoc(
     *      description="Create new calendar event",
     *      resource=true
     * )
     * @AclAncestor("oro_calendar_event_create")
     *
     * @return Response
     */
    public function postAction()
    {
        return $this->handleCreateRequest();
    }

    /**
     * Remove calendar event.
     *
     * @param int $id Calendar event id
     *
     * @ApiDoc(
     *      description="Remove calendar event",
     *      resource=true
     * )
     * @Acl(
     *      id="oro_calendar_event_delete",
     *      type="entity",
     *      class="OroCalendarBundle:CalendarEvent",
     *      permission="DELETE",
     *      group_name=""
     * )
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * @return ApiEntityManager
     */
    public function getManager()
    {
        return $this->get('oro_calendar.calendar_event.manager.api');
    }

    /**
     * @return Form
     */
    public function getForm()
    {
        return $this->get('oro_calendar.calendar_event.form.api');
    }

    /**
     * @return ApiFormHandler
     */
    public function getFormHandler()
    {
        return $this->get('oro_calendar.calendar_event.form.handler.api');
    }

    /**
     * {@inheritdoc}
     */
    protected function fixFormData(array &$data, $entity)
    {
        parent::fixFormData($data, $entity);

        if (isset($data['allDay']) && ($data['allDay'] === 'false' || $data['allDay'] === '0')) {
            $data['allDay'] = false;
        }

        // remove auxiliary attributes if any
        unset($data['createdAt']);
        unset($data['updatedAt']);
        unset($data['calendarAlias']);
        unset($data['editable']);
        unset($data['removable']);

        return true;
    }
}
