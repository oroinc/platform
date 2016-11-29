<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowActivationException;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

class WorkflowDefinitionEntityListener
{
    /** @var WorkflowRegistry */
    private $workflowRegistry;

    /**
     * @param WorkflowRegistry $workflowRegistry
     */
    public function __construct(WorkflowRegistry $workflowRegistry)
    {
        $this->workflowRegistry = $workflowRegistry;
    }

    /**
     * @param WorkflowDefinition $definition
     * @throws WorkflowActivationException
     */
    public function prePersist(WorkflowDefinition $definition)
    {
        if ($definition->isActive() && $definition->hasExclusiveActiveGroups()) {
            $workflows = $this->workflowRegistry->getActiveWorkflowsByActiveGroups(
                $definition->getExclusiveActiveGroups()
            );

            if (count($workflows) !== 0) {
                throw $this->generateException($definition, $workflows->getValues());
            }
        }
    }

    /**
     * @param WorkflowDefinition $definition
     * @param PreUpdateEventArgs $event
     * @throws WorkflowActivationException
     */
    public function preUpdate(WorkflowDefinition $definition, PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField('active') && $event->getNewValue('active') === true) {
            $storedWorkflows = $this->workflowRegistry->getActiveWorkflowsByActiveGroups(
                $definition->getExclusiveActiveGroups()
            );

            $conflictingWorkflows = $storedWorkflows->filter(function (Workflow $workflow) use ($definition) {
                return $definition->getName() !== $workflow->getName();
            });

            if (!$conflictingWorkflows->isEmpty()) {
                throw $this->generateException($definition, $conflictingWorkflows->getValues());
            }
        }
    }

    /**
     * @param WorkflowDefinition $definition
     * @param array|Workflow[] $workflows
     * @return WorkflowActivationException
     */
    private function generateException(WorkflowDefinition $definition, array $workflows)
    {
        $exclusiveActiveGroups = $definition->getExclusiveActiveGroups();

        $conflicts = [];
        foreach ($workflows as $workflow) {
            $groups = $workflow->getDefinition()->getExclusiveActiveGroups();
            foreach ($exclusiveActiveGroups as $group) {
                if (in_array($group, $groups, true)) {
                    $conflicts[] = sprintf(
                        'workflow `%s` by exclusive_active_group `%s`',
                        $workflow->getName(),
                        $group
                    );
                }
            }
        }

        return new WorkflowActivationException(sprintf(
            'Workflow `%s` cannot be activated as it conflicts with %s.',
            $definition->getName(),
            implode(', ', $conflicts)
        ));
    }
}
