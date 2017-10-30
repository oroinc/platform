<?php

namespace Oro\Bundle\WorkflowBundle\WorkflowData;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;

interface WorkflowDataUpdaterInterface
{
    /**
     * @param WorkflowDefinition $workflow
     * @param WorkflowData $data
     * @param object|null $source
     */
    public function update(WorkflowDefinition $workflow, WorkflowData $data, $source);

    /**
     * @param WorkflowDefinition $workflow
     * @param object|null $source
     * @return bool
     */
    public function isApplicable(WorkflowDefinition $workflow, $source);
}
