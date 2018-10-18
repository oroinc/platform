<?php

namespace Oro\Bundle\ApiBundle\DataTransformer;

use Oro\Component\EntitySerializer\DataTransformerInterface;

/**
 * Transforms an empty array to NULL.
 */
class EmptyArrayToNullTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($class, $property, $value, array $config, array $context)
    {
        if (is_array($value) && empty($value)) {
            return null;
        }

        return $value;
    }
}
