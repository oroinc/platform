<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Formatter;

use OpenApi\Annotations as OA;

/**
 * Renders OpenAPI specification in JSON pretty format.
 */
class JsonPrettyOpenApiFormatter implements OpenApiFormatterInterface
{
    #[\Override]
    public function format(OA\OpenApi $api): string
    {
        return $api->toJson(JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}
