<?php

namespace Oro\Bundle\SearchBundle\Api;

use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * Represents a service that provides class names for entities that can be searched via API.
 */
interface SearchEntityClassProviderInterface
{
    /**
     * Gets a list of entity classes that are accessible through API and can be searched by a search engine.
     *
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return array [entity class => entity search alias, ...]
     */
    public function getAccessibleEntityClasses(string $version, RequestType $requestType): array;

    /**
     * Gets a list of entity classes that can be searched via API.
     *
     * @param string      $version
     * @param RequestType $requestType
     *
     * @return array [entity class => entity search alias, ...]
     */
    public function getAllowedEntityClasses(string $version, RequestType $requestType): array;
}
