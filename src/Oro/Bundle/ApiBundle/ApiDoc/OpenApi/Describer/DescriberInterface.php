<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Describer;

use OpenApi\Annotations as OA;

/**
 * Represents a service that describes objects for OpenAPI specification.
 */
interface DescriberInterface
{
    public function describe(OA\OpenApi $api, array $options): void;
}
