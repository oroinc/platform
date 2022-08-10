<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * Delegates the check whether an entity is enabled for API
 * to a checker that is applicable for a specific API request type.
 */
class ChainResourceChecker implements ResourceCheckerInterface
{
    private ResourceCheckerRegistry $resourceCheckerRegistry;

    public function __construct(ResourceCheckerRegistry $resourceCheckerRegistry)
    {
        $this->resourceCheckerRegistry = $resourceCheckerRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public function isResourceEnabled(
        string $entityClass,
        string $action,
        string $version,
        RequestType $requestType
    ): bool {
        return $this->resourceCheckerRegistry->getResourceChecker($requestType)
            ->isResourceEnabled($entityClass, $action, $version, $requestType);
    }
}
