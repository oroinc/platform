<?php

namespace Oro\Component\EntitySerializer;

/**
 * Represents a transformer that is used to prepare a value to serialization.
 */
interface DataTransformerInterface
{
    /**
     * Prepares the given value for serialization.
     */
    public function transform(mixed $value, array $config, array $context): mixed;
}
