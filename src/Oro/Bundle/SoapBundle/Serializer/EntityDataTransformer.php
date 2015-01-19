<?php

namespace Oro\Bundle\SoapBundle\Serializer;

use Doctrine\ORM\Proxy\Proxy;

class EntityDataTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transformValue(&$value)
    {
        if ($value instanceof Proxy && method_exists($value, '__toString')) {
            $value = (string)$value;
        } elseif ($value instanceof \DateTime) {
            $value = $value->format('c');
        } elseif (is_object($value) && method_exists($value, '__toString')) {
            $value = (string)$value;
        }
    }
}
