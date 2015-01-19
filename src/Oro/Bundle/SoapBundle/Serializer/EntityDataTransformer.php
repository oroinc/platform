<?php

namespace Oro\Bundle\SoapBundle\Serializer;

class EntityDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transformValue($value)
    {
        if ($value instanceof \DateTime) {
            return $value->format('c');
        } elseif (is_object($value) && method_exists($value, '__toString')) {
            return (string)$value;
        }

        return $value;
    }
}
