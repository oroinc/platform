<?php

namespace Oro\Bundle\EntityConfigBundle\Config\Definition;

use Symfony\Component\Config\Definition\BooleanNode;

/**
 * The node for a normalized boolean value.
 * The "normalized" means that any non-boolean value is converted to a boolean value.
 */
class NormalizedBooleanNode extends BooleanNode
{
    #[\Override]
    protected function preNormalize($value): bool
    {
        return (bool)$value;
    }
}
