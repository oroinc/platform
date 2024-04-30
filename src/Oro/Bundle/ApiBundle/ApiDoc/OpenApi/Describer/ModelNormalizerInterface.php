<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Describer;

/**
 * Represents a service to normalize models.
 */
interface ModelNormalizerInterface
{
    public function normalizeModel(array $model, string $action, bool $isResponseModel): array;
}
