<?php

namespace Oro\Bundle\CalendarBundle\Controller\Api\Rest;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Util\Codes;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\HttpDateTimeParameterFilter;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\IdentifierToReferenceFilter;
use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\CalendarBundle\Exception\NotUserCalendarEvent;

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
     * @QueryParam(
     *     name="recurringEventId", requirements="\d+",
     *     nullable=true,
     *     description="Filter events associated with recurring event. Recurring event will be returned as well."
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
        $calendarId   = (int)$this->getRequest()->get('calendar');
        $subordinate  = (true == $this->getRequest()->get('subordinate'));
        $extendFields = $this->getExtendFieldNames('Oro\Bundle\CalendarBundle\Entity\CalendarEvent');
        $qb           = null;
        if ($this->getRequest()->get('start') && $this->getRequest()->get('end')) {
            $result = $this->get('oro_calendar.calendar_manager')->getCalendarEvents(
                $this->get('oro_security.security_facade')->getOrganization()->getId(),
                $this->getUser()->getId(),
                $calendarId,
                new \DateTime($this->getRequest()->get('start')),
                new \DateTime($this->getRequest()->get('end')),
                $subordinate,
                $extendFields
            );
        } elseif ($this->getRequest()->get('page') && $this->getRequest()->get('limit')) {
            $dateParamFilter  = new HttpDateTimeParameterFilter();
            $filterParameters = ['createdAt' => $dateParamFilter, 'updatedAt' => $dateParamFilter];
            $parameters = ['createdAt', 'updatedAt'];
            if ($this->getRequest()->get('recurringEventId')) {
                $filterParameters['recurringEventId'] = new IdentifierToReferenceFilter(
                    $this->getDoctrine(),
                    'OroCalendarBundle:CalendarEvent'
                );
                $parameters[] = 'recurringEventId';
            }
            $filterCriteria   = $this->getFilterCriteria(
                $parameters,
                $filterParameters,
                ['recurringEventId' => 'recurringEvent']
            );

            /** @var CalendarEventRepository $repo */
            $repo  = $this->getManager()->getRepository();
            $qb    = $repo->getUserEventListByRecurringEventQueryBuilder(
                $filterCriteria,
                $extendFields,
                (int)$this->getRequest()->get('recurringEventId')
            );
            $page  = (int)$this->getRequest()->get('page', 1);
            $limit = (int)$this->getRequest()->get('limit', self::ITEMS_PER_PAGE);
            $qb
                ->andWhere('c.id = :calendarId')
                ->setParameter('calendarId', $calendarId);
            $qb->setMaxResults($limit)
                ->setFirstResult($page > 0 ? ($page - 1) * $limit : 0);

            $result = $this->get('oro_calendar.calendar_event_normalizer.user')->getCalendarEvents(
                $calendarId,
                $qb->getQuery()
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
     * Get calendar event.
     *
     * @param int $id Calendar event id
     *
     * @ApiDoc(
     *      description="Get calendar event",
     *      resource=true
     * )
     * @AclAncestor("oro_calendar_event_view")
     *
     * @return Response
     */
    public function getAction($id)
    {
        /** @var CalendarEvent|null $entity */
        $entity = $this->getManager()->find($id);

        $result = null;
        $code   = Codes::HTTP_NOT_FOUND;
        if ($entity) {
            $result = $this->get('oro_calendar.calendar_event_normalizer.user')
                ->getCalendarEvent(
                    $entity,
                    null,
                    $this->getExtendFieldNames('Oro\Bundle\CalendarBundle\Entity\CalendarEvent')
                );
            $code   = Codes::HTTP_OK;
        }

        return $this->buildResponse($result ? : '', self::ACTION_READ, ['result' => $result], $code);
    }

    /**
     * Get calendar event supposing it is displayed in the specified calendar.
     *
     * @param int $id      The id of a calendar where an event is displayed
     * @param int $eventId Calendar event id
     *
     * @Get(
     *      "/calendars/{id}/events/{eventId}",
     *      requirements={"id"="\d+", "eventId"="\d+"}
     * )
     * @ApiDoc(
     *      description="Get calendar event supposing it is displayed in the specified calendar",
     *      resource=true
     * )
     * @AclAncestor("oro_calendar_event_view")
     *
     * @return Response
     */
    public function getByCalendarAction($id, $eventId)
    {
        /** @var CalendarEvent|null $entity */
        $entity = $this->getManager()->find($eventId);

        $result = null;
        $code   = Codes::HTTP_NOT_FOUND;
        if ($entity) {
            $result = $this->get('oro_calendar.calendar_event_normalizer.user')
                ->getCalendarEvent($entity, (int)$id);
            $code   = Codes::HTTP_OK;
        }

        return $this->buildResponse($result ? : '', self::ACTION_READ, ['result' => $result], $code);
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
     * @Post("calendarevents")
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
    public function handleUpdateRequest($id)
    {
        /** @var CalendarEvent $entity */
        $entity = $this->getManager()->find($id);

        if ($entity) {
            try {
                $entity = $this->processForm($entity);
                if ($entity) {
                    $response = $this->createResponseData($entity);
                    unset($response['id']);
                    $view = $this->view($response, Codes::HTTP_OK);
                } else {
                    $view = $this->view($this->getForm(), Codes::HTTP_BAD_REQUEST);
                }
            } catch (ForbiddenException $forbiddenEx) {
                $view = $this->view(['reason' => $forbiddenEx->getReason()], Codes::HTTP_FORBIDDEN);
            }
        } else {
            $view = $this->view(null, Codes::HTTP_NOT_FOUND);
        }

        return $this->buildResponse($view, self::ACTION_UPDATE, ['id' => $id, 'entity' => $entity]);
    }

    /**
     * {@inheritdoc}
     */
    public function handleCreateRequest($_ = null)
    {
        $isProcessed = false;

        $entity = call_user_func_array([$this, 'createEntity'], func_get_args());
        try {
            $entity = $this->processForm($entity);
            if ($entity) {
                $view        = $this->view($this->createResponseData($entity), Codes::HTTP_CREATED);
                $isProcessed = true;
            } else {
                $view = $this->view($this->getForm(), Codes::HTTP_BAD_REQUEST);
            }
        } catch (ForbiddenException $forbiddenEx) {
            $view = $this->view(['reason' => $forbiddenEx->getReason()], Codes::HTTP_FORBIDDEN);
        } catch (NotUserCalendarEvent $e) {
            $view = $this->view(['error' => $e->getMessage()], Codes::HTTP_BAD_REQUEST);
        }

        return $this->buildResponse($view, self::ACTION_CREATE, ['success' => $isProcessed, 'entity' => $entity]);
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

        if (isset($data['isCancelled']) && ($data['isCancelled'] === 'false' || $data['isCancelled'] === '0')) {
            $data['isCancelled'] = false;
        }

        if (isset($data['use_hangout']) && ($data['use_hangout'] === 'false' || $data['use_hangout'] === '0')) {
            $data['use_hangout'] = false;
        }

        if (isset($data['attendees']) && ($data['attendees'] === '')) {
            $data['attendees'] = null;
        }

        // remove auxiliary attributes if any
        unset($data['updatedAt'], $data['editable'], $data['removable'], $data['notifiable']);

        return true;
    }

    /**
     * @return SystemCalendarConfig
     */
    protected function getCalendarConfig()
    {
        return $this->get('oro_calendar.system_calendar_config');
    }

    /**
     * {@inheritdoc}
     */
    protected function getDeleteHandler()
    {
        return $this->get('oro_calendar.calendar_event.handler.delete');
    }

    /**
     * @param string $class
     *
     * @return array
     */
    protected function getExtendFieldNames($class)
    {
        $configProvider = $this->get('oro_entity_config.provider.extend');
        $configs        = $configProvider->filter(
            function (ConfigInterface $extendConfig) {
                return
                    $extendConfig->is('owner', ExtendScope::OWNER_CUSTOM) &&
                    ExtendHelper::isFieldAccessible($extendConfig) &&
                    !$extendConfig->has('target_entity') &&
                    !$extendConfig->is('is_serialized');
            },
            $class
        );

        return array_map(
            function (ConfigInterface $config) {
                return $config->getId()->getFieldName();
            },
            $configs
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function createResponseData($entity)
    {
        $response        = parent::createResponseData($entity);
        $serializedEvent = $this->get('oro_calendar.calendar_event_normalizer.user')->getCalendarEvent($entity);

        $response['notifiable']       = $serializedEvent['notifiable'];
        $response['invitationStatus'] = (string)$serializedEvent['invitationStatus'];

        return $response;
    }
}
