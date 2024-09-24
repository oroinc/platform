<?php

namespace Oro\Bundle\EntityConfigBundle\Config\Definition;

use Symfony\Component\Config\Definition\BooleanNode;

/**
 *  The node definition class for boolean type with normalization of value
 */
class NormalizedBooleanNode extends BooleanNode
{
    #[\Override]
    protected function preNormalize($value): bool
    {
        return (bool)$value;
    }
}
