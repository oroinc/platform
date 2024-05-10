<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Describer;

/**
 * Represents a service to get response headers for a specific API resource.
 */
interface ResponseHeaderProviderInterface
{
    public function getResponseHeaders(
        string $action,
        ?string $entityType,
        ?string $associationName,
        bool $isErrorResponse = false
    ): array;
}
