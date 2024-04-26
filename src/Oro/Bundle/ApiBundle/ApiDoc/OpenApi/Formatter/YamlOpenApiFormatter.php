<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Formatter;

use OpenApi\Annotations as OA;

/**
 * Renders OpenAPI specification in YAML format.
 */
class YamlOpenApiFormatter implements OpenApiFormatterInterface
{
    /**
     * {@inheritDoc}
     */
    public function format(OA\OpenApi $api): string
    {
        return $api->toYaml();
    }
}
