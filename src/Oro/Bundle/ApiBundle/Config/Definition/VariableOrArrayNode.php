<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Symfony\Component\Config\Definition\ArrayNode;

/**
 * The node that can have array values that should be merged.
 */
class VariableOrArrayNode extends ArrayNode
{
    /**
     * {@inheritdoc}
     */
    protected function preNormalize($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    protected function normalizeValue($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    protected function finalizeValue($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    protected function validateType($value)
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function mergeValues($leftSide, $rightSide)
    {
        if (\is_array($leftSide) && \is_array($rightSide)) {
            return parent::mergeValues($leftSide, $rightSide);
        }

        return $rightSide;
    }
}
