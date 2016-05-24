<?php

namespace Oro\Bundle\WorkflowBundle\Generator;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class ScheduledTransitionsDQLGenerator
{
    public function __construct()
    {
    }

    /**
     * @param WorkflowDefinition $definition
     * @param $transitionName
     * @param string $filterDQL
     */
    public function generate(WorkflowDefinition $definition, $transitionName, $filterDQL = '')
    {

    }
}
