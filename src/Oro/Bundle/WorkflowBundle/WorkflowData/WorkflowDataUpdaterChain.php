<?php

namespace Oro\Bundle\WorkflowBundle\WorkflowData;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;

class WorkflowDataUpdaterChain
{
    /** @var array|WorkflowDataUpdaterInterface[] */
    protected $updaters = [];

    /**
     * @param WorkflowDataUpdaterInterface $updater
     */
    public function addUpdater(WorkflowDataUpdaterInterface $updater)
    {
        $this->updaters[] = $updater;
    }

    /**
     * @param WorkflowDefinition $workflow
     * @param WorkflowData $data
     * @param object|null $source
     */
    public function update(WorkflowDefinition $workflow, WorkflowData $data, $source)
    {
        foreach ($this->updaters as $updater) {
            if ($updater->isApplicable($workflow, $source)) {
                $updater->update($workflow, $data, $source);
            }
        }
    }
}
