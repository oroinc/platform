<?php

namespace Oro\Bundle\EntityConfigBundle\Config\Definition;

use Symfony\Component\Config\Definition\Builder\BooleanNodeDefinition;

/**
 * The definition for a normalized boolean node.
 * The "normalized" means that any non-boolean value is converted to a boolean value.
 */
class NormalizedBooleanNodeDefinition extends BooleanNodeDefinition
{
    #[\Override]
    protected function instantiateNode(): NormalizedBooleanNode
    {
        return new NormalizedBooleanNode($this->name, $this->parent, $this->pathSeparator);
    }
}
