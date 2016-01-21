<?php

namespace Oro\Bundle\CalendarBundle\Controller\Api\Rest;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Util\Codes;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\CalendarBundle\Manager\CalendarPropertyApiEntityManager;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;

/**
 * @NamePrefix("oro_api_")
 */
class CalendarConnectionController extends RestController implements ClassResourceInterface
{
    /**
     * Get calendar connections.
     *
     * @param int $id User's calendar id
     *
     * @Get("/calendars/{id}/connections", requirements={"id"="\d+"})
     * @ApiDoc(
     *      description="Get calendar connections",
     *      resource=true
     * )
     * @AclAncestor("oro_calendar_view")
     *
     * @return Response
     * @throws \InvalidArgumentException
     */
    public function cgetAction($id)
    {
        $items = $this->getManager()->getCalendarManager()
            ->getCalendars(
                $this->get('oro_security.security_facade')->getOrganization()->getId(),
                $this->getUser()->getId(),
                $id
            );

        return new Response(json_encode($items), Codes::HTTP_OK);
    }

    /**
     * Update calendar connection.
     *
     * @param int $id Calendar connection id
     *
     * @Put("/calendarconnections/{id}", requirements={"id"="\d+"})
     * @ApiDoc(
     *      description="Update calendar connection",
     *      resource=true
     * )
     * @AclAncestor("oro_calendar_view")
     *
     * @return Response
     */
    public function putAction($id)
    {
        return $this->handleUpdateRequest($id);
    }

    /**
     * Create new calendar connection.
     *
     * @Post("/calendarconnections")
     * @ApiDoc(
     *      description="Create new calendar connection",
     *      resource=true
     * )
     * @AclAncestor("oro_calendar_view")
     *
     * @return Response
     */
    public function postAction()
    {
        return $this->handleCreateRequest();
    }

    /**
     * Remove calendar connection.
     *
     * @param int $id Calendar connection id
     *
     * @Delete("/calendarconnections/{id}", requirements={"id"="\d+"})
     * @ApiDoc(
     *      description="Remove calendar connection",
     *      resource=true
     * )
     * @AclAncestor("oro_calendar_view")
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * @return CalendarPropertyApiEntityManager
     */
    public function getManager()
    {
        return $this->get('oro_calendar.calendar_property.manager.api');
    }

    /**
     * @return Form
     */
    public function getForm()
    {
        return $this->get('oro_calendar.calendar_property.form.api');
    }

    /**
     * @return ApiFormHandler
     */
    public function getFormHandler()
    {
        return $this->get('oro_calendar.calendar_property.form.handler.api');
    }

    /**
     * {@inheritdoc}
     */
    protected function fixFormData(array &$data, $entity)
    {
        parent::fixFormData($data, $entity);

        unset(
            $data['calendarName'],
            $data['removable'],
            $data['canAddEvent'],
            $data['canEditEvent'],
            $data['canDeleteEvent']
        );

        return true;
    }
}
