<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Symfony\Component\Config\Definition\ArrayNode;

/**
 * The node that can have array values that should be merged.
 */
class VariableOrArrayNode extends ArrayNode
{
    #[\Override]
    protected function preNormalize($value): mixed
    {
        return $value;
    }

    #[\Override]
    protected function normalizeValue($value): mixed
    {
        return $value;
    }

    #[\Override]
    protected function finalizeValue($value): mixed
    {
        return $value;
    }

    #[\Override]
    protected function validateType($value): void
    {
    }

    #[\Override]
    protected function mergeValues($leftSide, $rightSide): mixed
    {
        if (\is_array($leftSide) && \is_array($rightSide)) {
            return parent::mergeValues($leftSide, $rightSide);
        }

        return $rightSide;
    }
}
