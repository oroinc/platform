<?php

namespace Oro\Bundle\ApiBundle\DataTransformer;

use Oro\Component\EntitySerializer\DataTransformerInterface;

/**
 * Transforms an empty array to NULL.
 */
class EmptyArrayToNullTransformer implements DataTransformerInterface
{
    #[\Override]
    public function transform(mixed $value, array $config, array $context): mixed
    {
        if (\is_array($value) && empty($value)) {
            return null;
        }

        return $value;
    }
}
