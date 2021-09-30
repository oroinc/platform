<?php

namespace Oro\Bundle\EntityConfigBundle\Config\Definition\Builder;

use Oro\Bundle\EntityConfigBundle\Config\Definition\NormalizedBooleanNode;
use Symfony\Component\Config\Definition\Builder\BooleanNodeDefinition;

/**
 *  The node definition class for boolean type with normalization of value
 */
class NormalizedBooleanNodeDefinition extends BooleanNodeDefinition
{
    /**
     * Instantiate a Node.
     */
    protected function instantiateNode(): NormalizedBooleanNode
    {
        return new NormalizedBooleanNode($this->name, $this->parent, $this->pathSeparator);
    }
}
