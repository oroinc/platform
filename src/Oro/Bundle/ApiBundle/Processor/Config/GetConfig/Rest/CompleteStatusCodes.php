<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig\Rest;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;
use Oro\Bundle\ApiBundle\Request\ApiActions;

/**
 * Adds possible status codes for the following actions executed in scope of REST API:
 * "get_list", "get", "update", "create", "delete", "delete_list", "get_subresource",
 * "get_relationship", "update_relationship", "add_relationship", "delete_relationship".
 * By performance reasons it is done in one processor.
 */
class CompleteStatusCodes extends AbstractCompleteStatusCodes
{
    /**
     * {@inheritdoc}
     */
    protected function addStatusCodes(StatusCodesConfig $statusCodes, $targetAction)
    {
        switch ($targetAction) {
            case ApiActions::GET_LIST:
                $this->addStatusCodesForGetList($statusCodes);
                break;
            case ApiActions::GET:
                $this->addStatusCodesForGet($statusCodes);
                break;
            case ApiActions::UPDATE:
                $this->addStatusCodesForUpdate($statusCodes);
                break;
            case ApiActions::CREATE:
                $this->addStatusCodesForCreate($statusCodes);
                break;
            case ApiActions::DELETE:
                $this->addStatusCodesForDelete($statusCodes);
                break;
            case ApiActions::DELETE_LIST:
                $this->addStatusCodesForDeleteList($statusCodes);
                break;
            case ApiActions::GET_SUBRESOURCE:
                $this->addStatusCodesForGetSubresource($statusCodes);
                break;
            case ApiActions::GET_RELATIONSHIP:
                $this->addStatusCodesForGetRelationship($statusCodes);
                break;
            case ApiActions::UPDATE_RELATIONSHIP:
                $this->addStatusCodesForUpdateRelationship($statusCodes);
                break;
            case ApiActions::ADD_RELATIONSHIP:
                $this->addStatusCodesForAddRelationship($statusCodes);
                break;
            case ApiActions::DELETE_RELATIONSHIP:
                $this->addStatusCodesForDeleteRelationship($statusCodes);
                break;
        }
        
        parent::addStatusCodes($statusCodes, $targetAction);
    }

    /**
     * Adds status codes for "get_list" action
     *
     * @param StatusCodesConfig $statusCodes
     */
    protected function addStatusCodesForGetList(StatusCodesConfig $statusCodes)
    {
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_OK,
            'Returned when successful'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_FORBIDDEN,
            'Returned when no permissions to get the entities'
        );
    }

    /**
     * Adds status codes for "get" action
     *
     * @param StatusCodesConfig $statusCodes
     */
    protected function addStatusCodesForGet(StatusCodesConfig $statusCodes)
    {
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_OK,
            'Returned when successful'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_FORBIDDEN,
            'Returned when no permissions to get the entity'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_NOT_FOUND,
            'Returned when the entity is not found'
        );
    }

    /**
     * Adds status codes for "update" action
     *
     * @param StatusCodesConfig $statusCodes
     */
    protected function addStatusCodesForUpdate(StatusCodesConfig $statusCodes)
    {
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_OK,
            'Returned when entity was successfully updated'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_BAD_REQUEST,
            'Returned when the request data is not valid'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_FORBIDDEN,
            'Returned when no permissions to update the entity'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_NOT_FOUND,
            'Returned when the entity is not found'
        );
    }

    /**
     * Adds status codes for "create" action
     *
     * @param StatusCodesConfig $statusCodes
     */
    protected function addStatusCodesForCreate(StatusCodesConfig $statusCodes)
    {
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_CREATED,
            'Returned when entity was successfully created'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_BAD_REQUEST,
            'Returned when the request data is not valid'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_FORBIDDEN,
            'Returned when no permissions to create the entity'
        );
    }

    /**
     * Adds status codes for "delete" action
     *
     * @param StatusCodesConfig $statusCodes
     */
    protected function addStatusCodesForDelete(StatusCodesConfig $statusCodes)
    {
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_NO_CONTENT,
            'Returned when the entity successfully deleted'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_FORBIDDEN,
            'Returned when no permissions to delete the entity'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_NOT_FOUND,
            'Returned when the entity is not found'
        );
    }

    /**
     * Adds status codes for "delete_list" action
     *
     * @param StatusCodesConfig $statusCodes
     */
    protected function addStatusCodesForDeleteList(StatusCodesConfig $statusCodes)
    {
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_NO_CONTENT,
            'Returned when the entities successfully deleted'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_FORBIDDEN,
            'Returned when no permissions to delete the entities'
        );
    }

    /**
     * Adds status codes for "get_subresource" action
     *
     * @param StatusCodesConfig $statusCodes
     */
    protected function addStatusCodesForGetSubresource(StatusCodesConfig $statusCodes)
    {
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_OK,
            'Returned when successful'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_FORBIDDEN,
            'Returned when no permissions to get the entities'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_NOT_FOUND,
            'Returned when when the parent entity does not exist'
        );
    }

    /**
     * Adds status codes for "get_relationship" action
     *
     * @param StatusCodesConfig $statusCodes
     */
    protected function addStatusCodesForGetRelationship(StatusCodesConfig $statusCodes)
    {
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_OK,
            'Returned when successful'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_FORBIDDEN,
            'Returned when no permissions to get the entities'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_NOT_FOUND,
            'Returned when when the parent entity of the relationship does not exist'
        );
    }

    /**
     * Adds status codes for "update_relationship" action
     *
     * @param StatusCodesConfig $statusCodes
     */
    protected function addStatusCodesForUpdateRelationship(StatusCodesConfig $statusCodes)
    {
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_NO_CONTENT,
            'Returned when an update of the relationship is successful'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_FORBIDDEN,
            'Returned when no permissions to update the relationship'
        );
    }

    /**
     * Adds status codes for "add_relationship" action
     *
     * @param StatusCodesConfig $statusCodes
     */
    protected function addStatusCodesForAddRelationship(StatusCodesConfig $statusCodes)
    {
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_NO_CONTENT,
            'Returned when an update of the relationship is successful'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_FORBIDDEN,
            'Returned when no permissions to update the relationship'
        );
    }

    /**
     * Adds status codes for "delete_relationship" action
     *
     * @param StatusCodesConfig $statusCodes
     */
    protected function addStatusCodesForDeleteRelationship(StatusCodesConfig $statusCodes)
    {
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_NO_CONTENT,
            'Returned when an update of the relationship is successful'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_FORBIDDEN,
            'Returned when no permissions to update the relationship'
        );
    }
}
