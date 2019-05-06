<?php

namespace Oro\Component\EntitySerializer;

/**
 * Represents a transformer that is used to prepare a value to serialization.
 */
interface DataTransformerInterface
{
    /**
     * Prepares the given value for serialization.
     *
     * @param mixed  $value
     * @param array  $config
     * @param array  $context
     *
     * @return mixed
     */
    public function transform($value, array $config, array $context);
}
