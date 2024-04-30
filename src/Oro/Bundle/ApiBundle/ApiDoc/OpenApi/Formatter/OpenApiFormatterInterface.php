<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Formatter;

use OpenApi\Annotations as OA;

/**
 * Represents OpenAPI specification renderer.
 */
interface OpenApiFormatterInterface
{
    public function format(OA\OpenApi $api): string;
}
