<?php

namespace Oro\Bundle\WorkflowBundle\Model\Filter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

interface WorkflowDefinitionFilterInterface
{
    /**
     * @param ArrayCollection|WorkflowDefinition[] $workflowDefinitions
     * @return ArrayCollection|WorkflowDefinition[]
     */
    public function filter(ArrayCollection $workflowDefinitions);
}
