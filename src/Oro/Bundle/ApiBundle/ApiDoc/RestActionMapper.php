<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutes;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides information about mapping between API actions and HTTP methods and about general API route names.
 */
class RestActionMapper
{
    /** @var RestRoutes */
    private $routes;

    /**
     * @param RestRoutes $routes
     */
    public function __construct(RestRoutes $routes)
    {
        $this->routes = $routes;
    }

    /**
     * @param string $templateRouteName
     *
     * @return string[]
     */
    public function getActions(string $templateRouteName): array
    {
        switch ($templateRouteName) {
            case $this->routes->getItemRouteName():
                return [
                    ApiActions::OPTIONS,
                    ApiActions::GET,
                    ApiActions::DELETE,
                    ApiActions::UPDATE
                ];
            case $this->routes->getListRouteName():
                return [
                    ApiActions::OPTIONS,
                    ApiActions::GET_LIST,
                    ApiActions::DELETE_LIST,
                    ApiActions::CREATE
                ];
            case $this->routes->getSubresourceRouteName():
                return [
                    ApiActions::OPTIONS,
                    ApiActions::GET_SUBRESOURCE,
                    ApiActions::UPDATE_SUBRESOURCE,
                    ApiActions::ADD_SUBRESOURCE,
                    ApiActions::DELETE_SUBRESOURCE
                ];
            case $this->routes->getRelationshipRouteName():
                return [
                    ApiActions::OPTIONS,
                    ApiActions::GET_RELATIONSHIP,
                    ApiActions::UPDATE_RELATIONSHIP,
                    ApiActions::ADD_RELATIONSHIP,
                    ApiActions::DELETE_RELATIONSHIP
                ];
        }

        return [];
    }

    /**
     * @return string[]
     */
    public function getActionsForResourcesWithoutIdentifier(): array
    {
        return [
            ApiActions::OPTIONS,
            ApiActions::GET,
            ApiActions::DELETE,
            ApiActions::CREATE,
            ApiActions::UPDATE
        ];
    }

    /**
     * @param string $action
     *
     * @return string
     *
     * @throws \LogicException if the given API action cannot be mapped to any HTTP method
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getMethod(string $action): string
    {
        switch ($action) {
            case ApiActions::GET:
            case ApiActions::GET_LIST:
            case ApiActions::GET_SUBRESOURCE:
            case ApiActions::GET_RELATIONSHIP:
                return Request::METHOD_GET;
            case ApiActions::DELETE:
            case ApiActions::DELETE_LIST:
            case ApiActions::DELETE_SUBRESOURCE:
            case ApiActions::DELETE_RELATIONSHIP:
                return Request::METHOD_DELETE;
            case ApiActions::UPDATE:
            case ApiActions::UPDATE_SUBRESOURCE:
            case ApiActions::UPDATE_RELATIONSHIP:
                return Request::METHOD_PATCH;
            case ApiActions::CREATE:
            case ApiActions::ADD_SUBRESOURCE:
            case ApiActions::ADD_RELATIONSHIP:
                return Request::METHOD_POST;
            case ApiActions::OPTIONS:
                return Request::METHOD_OPTIONS;
        }

        throw new \LogicException(\sprintf('Unsupported API action "%s".', $action));
    }
}
