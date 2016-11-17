<?php

namespace Oro\Bundle\WorkflowBundle\Model\Filter;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

interface WorkflowDefinitionFilterInterface
{
    /**
     * @param Collection|WorkflowDefinition[] $workflowDefinitions
     * @return Collection|WorkflowDefinition[]
     */
    public function filter(Collection $workflowDefinitions);
}
