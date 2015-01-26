<?php

namespace Oro\Bundle\SoapBundle\Serializer;

class EntityDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transformValue($value)
    {
        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string)$value;
            } elseif ($value instanceof \DateTime) {
                return $value->format('c');
            }
        }

        return $value;
    }
}
