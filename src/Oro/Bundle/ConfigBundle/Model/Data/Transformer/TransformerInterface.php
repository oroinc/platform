<?php

namespace  Oro\Bundle\ConfigBundle\Model\Data\Transformer;

/**
 * Transforms a value between different representations.
 *
 */
interface TransformerInterface
{
    /**
     * Transforms a value from the original representation to a transformed representation.
     *
     * @param mixed $value The value in the original representation
     *
     * @return mixed The value in the transformed representation
     */
    public function transform($value);
}
