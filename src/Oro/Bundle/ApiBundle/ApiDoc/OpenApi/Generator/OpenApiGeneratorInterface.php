<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Generator;

use OpenApi\Annotations as OA;

/**
 * Represents OpenAPI specification generator.
 */
interface OpenApiGeneratorInterface
{
    public function generate(array $options = []): OA\OpenApi;
}
