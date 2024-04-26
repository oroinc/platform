<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Describer;

/**
 * Represents a service to get request headers for a specific API resource.
 */
interface RequestHeaderProviderInterface
{
    public function getRequestHeaders(string $action, ?string $entityType, ?string $associationName): array;
}
