<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Adds "variable_or_array" node that can have array values that should be merged.
 */
class VariableOrArrayTreeBuilder extends NodeBuilder
{
    public function __construct()
    {
        parent::__construct();
        $this->nodeMapping['variable_or_array'] = VariableOrArrayNodeDefinition::class;
    }
}
