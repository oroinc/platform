<?php

namespace Oro\Component\EntitySerializer;

interface DataTransformerInterface
{
    /**
     * Prepares the given value for serialization
     *
     * @param string $class
     * @param string $property
     * @param mixed  $value
     * @param array  $config
     *
     * @return mixed
     */
    public function transform($class, $property, $value, $config);
}
