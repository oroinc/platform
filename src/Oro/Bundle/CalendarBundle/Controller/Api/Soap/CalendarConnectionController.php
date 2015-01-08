<?php

namespace Oro\Bundle\CalendarBundle\Controller\Api\Soap;

use Symfony\Component\Form\FormInterface;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Soap\SoapController;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;
use Oro\Bundle\CalendarBundle\Manager\CalendarPropertyApiEntityManager;

class CalendarConnectionController extends SoapController
{
    /**
     * Get calendar connections.
     *
     * @Soap\Method("getCalendarConnections")
     * @Soap\Param("page", phpType="int")
     * @Soap\Param("limit", phpType="int")
     * @Soap\Result(phpType = "Oro\Bundle\CalendarBundle\Entity\CalendarProperty[]")
     * @AclAncestor("oro_calendar_view")
     *
     * @throws \InvalidArgumentException
     */
    public function cgetAction($page = 1, $limit = 10)
    {
        return $this->handleGetListRequest($page, $limit);
    }

    /**
     * @Soap\Method("getCalendarConnection")
     * @Soap\Param("id", phpType = "int")
     * @Soap\Result(phpType = "Oro\Bundle\CalendarBundle\Entity\CalendarProperty")
     * @AclAncestor("oro_calendar_view")
     */
    public function getAction($id)
    {
        return $this->handleGetRequest($id);
    }

    /**
     * Update calendar connection.
     *
     * @Soap\Method("updateCalendarConnection")
     * @Soap\Param("id", phpType = "int")
     * @Soap\Param("calendarProperty", phpType = "Oro\Bundle\CalendarBundle\Entity\CalendarProperty")
     * @Soap\Result(phpType = "boolean")
     * @AclAncestor("oro_calendar_view")
     */
    public function updateAction($id, $calendarProperty)
    {
        return $this->handleUpdateRequest($id);
    }

    /**
     * Create new calendar connection.
     *
     * @Soap\Method("createCalendarConnection")
     * @Soap\Param("calendarProperty", phpType = "Oro\Bundle\CalendarBundle\Entity\CalendarProperty")
     * @Soap\Result(phpType = "int")
     * @AclAncestor("oro_calendar_view")
     */
    public function createAction($calendarProperty)
    {
        return $this->handleCreateRequest();
    }

    /**
     * Remove calendar connection.
     *
     * @Soap\Method("deleteCalendarConnection")
     * @Soap\Param("id", phpType = "int")
     * @Soap\Result(phpType = "boolean")
     * @AclAncestor("oro_calendar_view")
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
        return $this->container->get('oro_calendar.calendar_property.manager.api');
    }

    /**
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->container->get('oro_calendar.calendar_property.form.soap.api');
    }

    /**
     * @return ApiFormHandler
     */
    public function getFormHandler()
    {
        return $this->container->get('oro_calendar.calendar_property.form.handler.soap.api');
    }
}
