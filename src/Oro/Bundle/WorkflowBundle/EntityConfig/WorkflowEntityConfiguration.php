<?php

namespace Oro\Bundle\WorkflowBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations entity config for workflow scope.
 */
class WorkflowEntityConfiguration implements EntityConfigInterface
{
    public function getSectionName(): string
    {
        return 'workflow';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->node('show_step_in_grid', 'normalized_boolean')
                ->info('`boolean` if TRUE, then a workflow step is displayed in the grid.')
                ->defaultTrue()
            ->end()
        ;
    }
}
