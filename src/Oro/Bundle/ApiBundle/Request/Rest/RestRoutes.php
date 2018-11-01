<?php

namespace Oro\Bundle\ApiBundle\Request\Rest;

/**
 * Provides REST API route names.
 */
class RestRoutes
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
     * Gets the route name that is used to handle "/api/{entity}/{id}" requests.
     *
     * @return string
     */
    public function getItemRouteName(): string
    {
        return $this->itemRouteName;
    }

    /**
     * Gets the route name that is used to handle "/api/{entity}" requests.
     *
     * @return string
     */
    public function getListRouteName(): string
    {
        return $this->listRouteName;
    }

    /**
     * Gets the route name that is used to handle "/api/{entity}/{id}/{association}" requests.
     *
     * @return string
     */
    public function getSubresourceRouteName(): string
    {
        return $this->subresourceRouteName;
    }

    /**
     * Gets the route name that is used to handle "/api/{entity}/{id}/relationships/{association}" requests.
     *
     * @return string
     */
    public function getRelationshipRouteName(): string
    {
        return $this->relationshipRouteName;
    }
}
