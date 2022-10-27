<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;

/**
 * The definition for variable node that can have array values that should be merged.
 */
class VariableOrArrayNodeDefinition extends NodeDefinition
{
    /**
     * {@inheritdoc}
     */
    protected function createNode()
    {
        return new VariableOrArrayNode($this->name, $this->parent);
    }
}
