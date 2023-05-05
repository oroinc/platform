<?php

namespace Oro\Bundle\ApiBundle\Request\Rest;

/**
 * Provides REST API route names.
 */
class RestRoutes
{
    private string $itemRouteName;
    private string $listRouteName;
    private string $subresourceRouteName;
    private string $relationshipRouteName;

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
     * Gets the route name that is used to handle "/api/{entity}/{id}" requests.
     */
    public function getItemRouteName(): string
    {
        return $this->itemRouteName;
    }

    /**
     * Gets the route name that is used to handle "/api/{entity}" requests.
     */
    public function getListRouteName(): string
    {
        return $this->listRouteName;
    }

    /**
     * Gets the route name that is used to handle "/api/{entity}/{id}/{association}" requests.
     */
    public function getSubresourceRouteName(): string
    {
        return $this->subresourceRouteName;
    }

    /**
     * Gets the route name that is used to handle "/api/{entity}/{id}/relationships/{association}" requests.
     */
    public function getRelationshipRouteName(): string
    {
        return $this->relationshipRouteName;
    }
}
