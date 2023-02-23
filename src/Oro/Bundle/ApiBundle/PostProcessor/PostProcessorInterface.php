<?php

namespace Oro\Bundle\ApiBundle\PostProcessor;

/**
 * Represents a post processor that is used to post process a field value.
 */
interface PostProcessorInterface
{
    /**
     * Prepares the given value for serialization.
     */
    public function process(mixed $value, array $options): mixed;
}
