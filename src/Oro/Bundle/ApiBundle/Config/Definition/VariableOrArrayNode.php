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
    protected function preNormalize($value): mixed
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    protected function normalizeValue($value): mixed
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    protected function finalizeValue($value): mixed
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
    protected function mergeValues($leftSide, $rightSide): mixed
    {
        if (\is_array($leftSide) && \is_array($rightSide)) {
            return parent::mergeValues($leftSide, $rightSide);
        }

        return $rightSide;
    }
}
