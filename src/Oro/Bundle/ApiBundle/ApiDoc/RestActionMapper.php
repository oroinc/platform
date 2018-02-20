<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Request\ApiActions;

class RestActionMapper
{
    /** @var string */
    private $itemRouteName;

    /** @var string */
    private $listRouteName;

    /** @var string */
    private $subresourceRouteName;

    /** @var string */
    private $relationshipRouteName;

    /**
     * @param string $itemRouteName
     * @param string $listRouteName
     * @param string $subresourceRouteName
     * @param string $relationshipRouteName
     */
    public function __construct(
        string $itemRouteName,
        string $listRouteName,
        string $subresourceRouteName,
        string $relationshipRouteName
    ) {
        $this->itemRouteName = $itemRouteName;
        $this->listRouteName = $listRouteName;
        $this->subresourceRouteName = $subresourceRouteName;
        $this->relationshipRouteName = $relationshipRouteName;
    }


    /**
     * @param string $templateRouteName
     *
     * @return string[]
     */
    public function getActions(string $templateRouteName): array
    {
        switch ($templateRouteName) {
            case $this->itemRouteName:
                return [
                    ApiActions::GET,
                    ApiActions::DELETE,
                    ApiActions::UPDATE
                ];
            case $this->listRouteName:
                return [
                    ApiActions::GET_LIST,
                    ApiActions::DELETE_LIST,
                    ApiActions::CREATE
                ];
            case $this->subresourceRouteName:
                return [
                    ApiActions::GET_SUBRESOURCE
                ];
            case $this->relationshipRouteName:
                return [
                    ApiActions::GET_RELATIONSHIP,
                    ApiActions::UPDATE_RELATIONSHIP,
                    ApiActions::ADD_RELATIONSHIP,
                    ApiActions::DELETE_RELATIONSHIP
                ];
        }

        return [];
    }

    /**
     * @param string $action
     *
     * @return string
     *
     * @throws \LogicException if the given API action cannot be mapped to any HTTP method
     */
    public function getMethod(string $action): string
    {
        switch ($action) {
            case ApiActions::GET:
            case ApiActions::GET_LIST:
            case ApiActions::GET_SUBRESOURCE:
            case ApiActions::GET_RELATIONSHIP:
                return 'GET';
            case ApiActions::DELETE:
            case ApiActions::DELETE_LIST:
            case ApiActions::DELETE_RELATIONSHIP:
                return 'DELETE';
            case ApiActions::UPDATE:
            case ApiActions::UPDATE_RELATIONSHIP:
                return 'PATCH';
            case ApiActions::CREATE:
            case ApiActions::ADD_RELATIONSHIP:
                return 'POST';
        }

        throw new \LogicException(sprintf('Unsupported API action "%s".', $action));
    }

    /**
     * @return string
     */
    public function getItemRouteName(): string
    {
        return $this->itemRouteName;
    }

    /**
     * @return string
     */
    public function getListRouteName(): string
    {
        return $this->listRouteName;
    }

    /**
     * @return string
     */
    public function getSubresourceRouteName(): string
    {
        return $this->subresourceRouteName;
    }

    /**
     * @return string
     */
    public function getRelationshipRouteName(): string
    {
        return $this->relationshipRouteName;
    }
}
