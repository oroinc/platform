<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * Represents a service to check whether an entity is enabled for API.
 */
interface ResourceCheckerInterface
{
    /**
     * Checks whether a given entity is enabled for API.
     *
     * @param string      $entityClass The FQCN of an entity
     * @param string      $action      The API action, {@see \Oro\Bundle\ApiBundle\Request\ApiAction}
     * @param string      $version     The API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     *
     * @return bool
     */
    public function isResourceEnabled(
        string $entityClass,
        string $action,
        string $version,
        RequestType $requestType
    ): bool;
}
