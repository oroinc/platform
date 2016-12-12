<?php

namespace Oro\Bundle\WorkflowBundle\Model\Filter;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

interface WorkflowDefinitionFilterInterface
{
    /**
     * @param WorkflowDefinition[]|Collection $workflowDefinitions
     * @return WorkflowDefinition[]|Collection
     */
    public function filter(Collection $workflowDefinitions);
}
