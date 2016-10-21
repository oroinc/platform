<?php

namespace Oro\Component\EntitySerializer;

class ValueTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($class, $property, $value, array $config, array $context)
    {
        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                $value = (string)$value;
            } elseif ($value instanceof \DateTime) {
                $value = $value->format('c');
            }
        }

        return $value;
    }
}
