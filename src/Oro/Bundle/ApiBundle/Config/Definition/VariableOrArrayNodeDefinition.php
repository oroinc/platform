<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\NodeInterface;

/**
 * The definition for variable node that can have array values that should be merged.
 */
class VariableOrArrayNodeDefinition extends NodeDefinition
{
    #[\Override]
    protected function createNode(): NodeInterface
    {
        return new VariableOrArrayNode($this->name, $this->parent);
    }
}
