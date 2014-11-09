<?php

namespace Oro\Bundle\CalendarBundle\Controller\Api\Soap;

use Symfony\Component\Form\FormInterface;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Soap\SoapController;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;

use Oro\Bundle\CalendarBundle\Manager\CalendarPropertyApiEntityManager;
use Oro\Bundle\CalendarBundle\Entity\CalendarPropertySoap;

class CalendarConnectionController extends SoapController
{
    /**
     * Get calendar connections.
     *
     * @Soap\Method("getCalendarConnections")
     * @Soap\Param("id", phpType = "int")
     * @Soap\Result(phpType = "Oro\Bundle\CalendarBundle\Entity\CalendarPropertySoap[]")
     * @AclAncestor("oro_calendar_view")
     *
     * @throws \InvalidArgumentException
     */
    public function cgetAction($id)
    {
        $items = $this->getManager()->getCalendarManager()
            ->getCalendars($this->getUser()->getId(), $id);

        return $this->transformToSoapEntities($items);
    }

    /**
     * Update calendar connection.
     *
     * @Soap\Method("updateCalendarConnection")
     * @Soap\Param("id", phpType = "int")
     * @Soap\Param("calendarConnection", phpType = "Oro\Bundle\CalendarBundle\Entity\CalendarProperty")
     * @Soap\Result(phpType = "boolean")
     * @AclAncestor("oro_calendar_view")
     */
    public function updateAction($id, $calendarConnection)
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
//        var_dump($calendarProperty);
//        die;
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

    /**
     * Get a user from the Security Context
     *
     * @return mixed
     *
     * @throws \LogicException If SecurityBundle is not available
     *
     * @see Symfony\Component\Security\Core\Authentication\Token\TokenInterface::getUser()
     */
    public function getUser()
    {
        if (!$this->container->has('security.context')) {
            throw new \LogicException('The SecurityBundle is not registered in your application.');
        }

        if (null === $token = $this->container->get('security.context')->getToken()) {
            return;
        }

        if (!is_object($user = $token->getUser())) {
            return;
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    protected function transformToSoapEntity($entity)
    {
        if (is_array($entity)) {
            $soapEntity = new CalendarPropertySoap();
            $soapEntity->soapInit($entity);

            return $soapEntity;
        }
        return $entity;
    }
}
