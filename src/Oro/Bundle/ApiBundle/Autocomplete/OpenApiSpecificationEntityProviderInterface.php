<?php

namespace Oro\Bundle\ApiBundle\Autocomplete;

/**
 * Represents a service that provides a list of all entities available for OpenAPI specification.
 */
interface OpenApiSpecificationEntityProviderInterface
{
    /**
     * @return OpenApiSpecificationEntity[]
     */
    public function getEntities(string $view): array;
}
