<?php

namespace Oro\Bundle\WorkflowBundle\Helper;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

class WorkflowDeactivationHelper
{
    /** @var WorkflowRegistry */
    protected $workflowRegistry;

    /** @var WorkflowTranslationHelper */
    protected $translationHelper;

    /**
     * @param WorkflowRegistry $workflowRegistry
     * @param WorkflowTranslationHelper $translationHelper
     */
    public function __construct(WorkflowRegistry $workflowRegistry, WorkflowTranslationHelper $translationHelper)
    {
        $this->workflowRegistry = $workflowRegistry;
        $this->translationHelper = $translationHelper;
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     *
     * @return array
     */
    public function getWorkflowsForManualDeactivation(WorkflowDefinition $workflowDefinition)
    {
        $exclusion = $this->getWorkflows($workflowDefinition);
        $workflows = $this->workflowRegistry->getActiveWorkflows()
            ->map(
                function (Workflow $workflow) use ($exclusion) {
                    if ($exclusion->contains($workflow)) {
                        return null;
                    }

                    return $this->translationHelper->findWorkflowTranslation(
                        $workflow->getLabel(),
                        $workflow->getName()
                    );
                }
            );

        $choices = array_filter($workflows->toArray());

        natsort($choices);

        return $choices;
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     *
     * @return Collection|Workflow[]
     */
    public function getWorkflowsToDeactivation(WorkflowDefinition $workflowDefinition)
    {
        return $this->getWorkflows($workflowDefinition, true);
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @param bool $excludeOriginal
     *
     * @return Collection|Workflow[]
     */
    private function getWorkflows(WorkflowDefinition $workflowDefinition, $excludeOriginal = false)
    {
        $workflows = $this->workflowRegistry->getActiveWorkflowsByActiveGroups(
            $workflowDefinition->getExclusiveActiveGroups()
        );

        if ($excludeOriginal) {
            $workflows = $workflows->filter(
                function (Workflow $workflow) use ($workflowDefinition) {
                    return $workflow->getName() !== $workflowDefinition->getName();
                }
            );
        }

        return $workflows;
    }
}
