<?php

namespace Oro\Bundle\ConfigBundle\Config;

/**
 * Transforms a config value between different representations.
 */
interface DataTransformerInterface
{
    /**
     * Transforms a config value.
     *
     * @param mixed $value The value to be transformed
     *
     * @return mixed The transformed value
     */
    public function transform($value);
}
