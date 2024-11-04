<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Provider\RunningWorkflowProvider;

/**
 * Allows only one running workflow from the sane exclusive record group.
 */
class WorkflowExclusiveRecordGroupFilter implements WorkflowApplicabilityFilterInterface
{
    public function __construct(
        private readonly RunningWorkflowProvider $runningWorkflowProvider
    ) {
    }

    #[\Override]
    public function filter(ArrayCollection $workflows, WorkflowRecordContext $context): ArrayCollection
    {
        $lockedGroup = $this->retrieveLockedGroups($workflows, $context);
        if (!$lockedGroup) {
            return $workflows;
        }

        return $workflows->filter(
            function (Workflow $workflow) use (&$lockedGroup) {
                $definition = $workflow->getDefinition();
                if ($definition->hasExclusiveRecordGroups()) {
                    $name = $workflow->getName();
                    foreach ($definition->getExclusiveRecordGroups() as $recordGroup) {
                        if (\array_key_exists($recordGroup, $lockedGroup) && $lockedGroup[$recordGroup] !== $name) {
                            return false;
                        }
                    }
                }

                return true;
            }
        );
    }

    private function retrieveLockedGroups(ArrayCollection $workflows, WorkflowRecordContext $context): array
    {
        $runningWorkflowNames = $this->runningWorkflowProvider->getRunningWorkflowNames($context->getEntity());
        if (!$runningWorkflowNames) {
            // no locks as no workflows in progress
            return [];
        }

        $lockedGroups = [];
        // as workflows comes in order of its priorities then highest one must replace/override lower one
        $workflows = array_reverse($workflows->toArray());
        /**@var Workflow $workflow */
        foreach ($workflows as $workflow) {
            $definition = $workflow->getDefinition();
            $workflowName = $definition->getName();
            if ($definition->hasExclusiveRecordGroups() && \in_array($workflowName, $runningWorkflowNames, true)) {
                foreach ($definition->getExclusiveRecordGroups() as $recordGroup) {
                    $lockedGroups[$recordGroup] = $workflowName;
                }
            }
        }

        return $lockedGroups;
    }
}
