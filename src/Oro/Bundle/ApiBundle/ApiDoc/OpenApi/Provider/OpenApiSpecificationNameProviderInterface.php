<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Provider;

/**
 * Represents a service that provides OpenAPI specification name.
 */
interface OpenApiSpecificationNameProviderInterface
{
    public function getOpenApiSpecificationName(string $view): string;
}
