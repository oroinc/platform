<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Defines the contract for filtering workflows based on applicability to a specific record.
 *
 * Implementations filter a collection of workflows to return only those that are applicable
 * to a given entity record based on workflow scope and configuration rules.
 */
interface WorkflowApplicabilityFilterInterface
{
    /**
     * @param ArrayCollection|Workflow[] $workflows Named collection of Workflow instances
     *                                              with structure: ['workflowName' => Workflow $worfklowInstance]
     * @param WorkflowRecordContext $context
     *
     * @return ArrayCollection
     */
    public function filter(ArrayCollection $workflows, WorkflowRecordContext $context);
}
