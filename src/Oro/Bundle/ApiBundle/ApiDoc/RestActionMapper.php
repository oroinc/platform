<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutes;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides information about mapping between API actions and HTTP methods and about general API route names.
 */
class RestActionMapper
{
    private RestRoutes $routes;

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
                    ApiAction::OPTIONS,
                    ApiAction::GET,
                    ApiAction::DELETE,
                    ApiAction::UPDATE
                ];
            case $this->routes->getListRouteName():
                return [
                    ApiAction::OPTIONS,
                    ApiAction::GET_LIST,
                    ApiAction::DELETE_LIST,
                    ApiAction::CREATE,
                    ApiAction::UPDATE_LIST
                ];
            case $this->routes->getSubresourceRouteName():
                return [
                    ApiAction::OPTIONS,
                    ApiAction::GET_SUBRESOURCE,
                    ApiAction::UPDATE_SUBRESOURCE,
                    ApiAction::ADD_SUBRESOURCE,
                    ApiAction::DELETE_SUBRESOURCE
                ];
            case $this->routes->getRelationshipRouteName():
                return [
                    ApiAction::OPTIONS,
                    ApiAction::GET_RELATIONSHIP,
                    ApiAction::UPDATE_RELATIONSHIP,
                    ApiAction::ADD_RELATIONSHIP,
                    ApiAction::DELETE_RELATIONSHIP
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
            ApiAction::OPTIONS,
            ApiAction::GET,
            ApiAction::DELETE,
            ApiAction::CREATE,
            ApiAction::UPDATE
        ];
    }

    /**
     * @throws \LogicException if the given API action cannot be mapped to any HTTP method
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getMethod(string $action): string
    {
        switch ($action) {
            case ApiAction::GET:
            case ApiAction::GET_LIST:
            case ApiAction::GET_SUBRESOURCE:
            case ApiAction::GET_RELATIONSHIP:
                return Request::METHOD_GET;
            case ApiAction::DELETE:
            case ApiAction::DELETE_LIST:
            case ApiAction::DELETE_SUBRESOURCE:
            case ApiAction::DELETE_RELATIONSHIP:
                return Request::METHOD_DELETE;
            case ApiAction::UPDATE:
            case ApiAction::UPDATE_SUBRESOURCE:
            case ApiAction::UPDATE_RELATIONSHIP:
            case ApiAction::UPDATE_LIST:
                return Request::METHOD_PATCH;
            case ApiAction::CREATE:
            case ApiAction::ADD_SUBRESOURCE:
            case ApiAction::ADD_RELATIONSHIP:
                return Request::METHOD_POST;
            case ApiAction::OPTIONS:
                return Request::METHOD_OPTIONS;
        }

        throw new \LogicException(sprintf('Unsupported API action "%s".', $action));
    }
}
