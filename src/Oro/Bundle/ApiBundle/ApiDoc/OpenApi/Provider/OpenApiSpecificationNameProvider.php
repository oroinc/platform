<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Provider;

/**
 * Provides OpenAPI specification name.
 */
class OpenApiSpecificationNameProvider implements OpenApiSpecificationNameProviderInterface
{
    /** @var array [api documentation view => api label, ...] */
    private array $viewLabels;

    public function __construct(array $viewLabels)
    {
        $this->viewLabels = $viewLabels;
    }

    /**
     * {@inheritDoc}
     */
    public function getOpenApiSpecificationName(string $view): string
    {
        return $this->viewLabels[$view] ?? 'REST API';
    }
}
