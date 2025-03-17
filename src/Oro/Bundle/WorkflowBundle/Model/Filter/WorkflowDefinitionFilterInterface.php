<?php

namespace Oro\Bundle\WorkflowBundle\Model\Filter;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

/**
 * Represents a service to filter workflow definitions.
 */
interface WorkflowDefinitionFilterInterface
{
    /**
     * @param Collection<int, WorkflowDefinition> $workflowDefinitions
     *
     * @return Collection<int, WorkflowDefinition>
     */
    public function filter(Collection $workflowDefinitions): Collection;
}
