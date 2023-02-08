<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\Rest;

use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds possible status codes for the following actions executed in scope of REST API:
 * "get_list", "get", "update", "update_list", "create", "delete", "delete_list",
 * "get_subresource", "update_subresource", "add_subresource", "delete_subresource",
 * "get_relationship", "update_relationship", "add_relationship", "delete_relationship"
 * and "options".
 * By performance reasons it is done in one processor.
 */
class CompleteStatusCodes extends AbstractCompleteStatusCodes
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function addStatusCodes(StatusCodesConfig $statusCodes, ?string $targetAction): void
    {
        switch ($targetAction) {
            case ApiAction::GET_LIST:
                $this->addStatusCodesForGetList($statusCodes);
                break;
            case ApiAction::GET:
                $this->addStatusCodesForGet($statusCodes);
                break;
            case ApiAction::UPDATE:
                $this->addStatusCodesForUpdate($statusCodes);
                break;
            case ApiAction::UPDATE_LIST:
                $this->addStatusCodesForUpdateList($statusCodes);
                break;
            case ApiAction::CREATE:
                $this->addStatusCodesForCreate($statusCodes);
                break;
            case ApiAction::DELETE:
                $this->addStatusCodesForDelete($statusCodes);
                break;
            case ApiAction::DELETE_LIST:
                $this->addStatusCodesForDeleteList($statusCodes);
                break;
            case ApiAction::GET_SUBRESOURCE:
                $this->addStatusCodesForGetSubresource($statusCodes);
                break;
            case ApiAction::UPDATE_SUBRESOURCE:
                $this->addStatusCodesForUpdateSubresource($statusCodes);
                break;
            case ApiAction::ADD_SUBRESOURCE:
                $this->addStatusCodesForAddSubresource($statusCodes);
                break;
            case ApiAction::DELETE_SUBRESOURCE:
                $this->addStatusCodesForDeleteSubresource($statusCodes);
                break;
            case ApiAction::GET_RELATIONSHIP:
                $this->addStatusCodesForGetRelationship($statusCodes);
                break;
            case ApiAction::UPDATE_RELATIONSHIP:
                $this->addStatusCodesForUpdateRelationship($statusCodes);
                break;
            case ApiAction::ADD_RELATIONSHIP:
                $this->addStatusCodesForAddRelationship($statusCodes);
                break;
            case ApiAction::DELETE_RELATIONSHIP:
                $this->addStatusCodesForDeleteRelationship($statusCodes);
                break;
            case ApiAction::OPTIONS:
                $this->addStatusCodesForOptions($statusCodes);
                break;
        }

        parent::addStatusCodes($statusCodes, $targetAction);
    }

    /**
     * Adds status codes for "get_list" action
     */
    protected function addStatusCodesForGetList(StatusCodesConfig $statusCodes): void
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
     */
    protected function addStatusCodesForGet(StatusCodesConfig $statusCodes): void
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
     */
    protected function addStatusCodesForUpdate(StatusCodesConfig $statusCodes): void
    {
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_OK,
            'Returned when the entity was successfully updated'
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
     * Adds status codes for "update_list" action
     */
    protected function addStatusCodesForUpdateList(StatusCodesConfig $statusCodes): void
    {
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_ACCEPTED,
            'Returned when request data was accepted to asynchronous processing'
        );
    }

    /**
     * Adds status codes for "create" action
     */
    protected function addStatusCodesForCreate(StatusCodesConfig $statusCodes): void
    {
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_CREATED,
            'Returned when the entity was successfully created'
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
     */
    protected function addStatusCodesForDelete(StatusCodesConfig $statusCodes): void
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
     */
    protected function addStatusCodesForDeleteList(StatusCodesConfig $statusCodes): void
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
     */
    protected function addStatusCodesForGetSubresource(StatusCodesConfig $statusCodes): void
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
            'Returned when the parent entity does not exist'
        );
    }

    /**
     * Adds status codes for "update_subresource" action
     */
    protected function addStatusCodesForUpdateSubresource(StatusCodesConfig $statusCodes): void
    {
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_OK,
            'Returned when the parent entity was successfully updated'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_FORBIDDEN,
            'Returned when no permissions to update the parent entity'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_NOT_FOUND,
            'Returned when the parent entity does not exist'
        );
    }

    /**
     * Adds status codes for "add_subresource" action
     */
    protected function addStatusCodesForAddSubresource(StatusCodesConfig $statusCodes): void
    {
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_OK,
            'Returned when the parent entity was successfully updated'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_FORBIDDEN,
            'Returned when no permissions to update the parent entity'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_NOT_FOUND,
            'Returned when the parent entity does not exist'
        );
    }

    /**
     * Adds status codes for "delete_subresource" action
     */
    protected function addStatusCodesForDeleteSubresource(StatusCodesConfig $statusCodes): void
    {
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_OK,
            'Returned when the parent entity was successfully updated'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_FORBIDDEN,
            'Returned when no permissions to update the parent entity'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_NOT_FOUND,
            'Returned when the parent entity does not exist'
        );
    }

    /**
     * Adds status codes for "get_relationship" action
     */
    protected function addStatusCodesForGetRelationship(StatusCodesConfig $statusCodes): void
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
            'Returned when the parent entity of the relationship does not exist'
        );
    }

    /**
     * Adds status codes for "update_relationship" action
     */
    protected function addStatusCodesForUpdateRelationship(StatusCodesConfig $statusCodes): void
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
     */
    protected function addStatusCodesForAddRelationship(StatusCodesConfig $statusCodes): void
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
     */
    protected function addStatusCodesForDeleteRelationship(StatusCodesConfig $statusCodes): void
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
     * Adds status codes for "options" action
     */
    protected function addStatusCodesForOptions(StatusCodesConfig $statusCodes): void
    {
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_OK,
            'Returned when successful'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_BAD_REQUEST,
            'Returned when the request data is not valid'
        );
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_NOT_FOUND,
            'Returned when the entity is not found'
        );
    }
}
