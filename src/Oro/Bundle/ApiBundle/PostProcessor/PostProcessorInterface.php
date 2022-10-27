<?php

namespace Oro\Bundle\ApiBundle\PostProcessor;

/**
 * Represents a post processor that is used to post process a field value.
 */
interface PostProcessorInterface
{
    /**
     * Prepares the given value for serialization.
     *
     * @param mixed $value
     * @param array $options
     *
     * @return mixed
     */
    public function process($value, array $options);
}
