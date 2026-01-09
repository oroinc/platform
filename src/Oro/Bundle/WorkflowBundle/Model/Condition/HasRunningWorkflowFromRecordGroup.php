<?php

namespace Oro\Bundle\WorkflowBundle\Model\Condition;

use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Provider\RunningWorkflowProvider;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

/**
 * Checks whether an entity has a running workflow from a specified exclusive record group.
 */
class HasRunningWorkflowFromRecordGroup extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    private string $groupName;
    private mixed $entity;

    public function __construct(
        private readonly WorkflowManager $workflowManager,
        private readonly RunningWorkflowProvider $runningWorkflowProvider
    ) {
    }

    #[\Override]
    public function getName(): string
    {
        return 'has_running_workflow_from_record_group';
    }

    #[\Override]
    public function initialize(array $options): self
    {
        if (empty($options['group_name'])) {
            throw new InvalidArgumentException('Group name parameter is required');
        }
        if (empty($options['entity'])) {
            throw new InvalidArgumentException('Entity parameter is required');
        }

        $this->groupName = $options['group_name'];
        $this->entity = $options['entity'];

        return $this;
    }

    #[\Override]
    protected function isConditionAllowed(mixed $context): bool
    {
        $entity = $this->resolveValue($context, $this->entity);
        $runningWorkflowNames = $this->runningWorkflowProvider->getRunningWorkflowNames($entity);
        if (!$runningWorkflowNames) {
            return false;
        }
        foreach ($runningWorkflowNames as $workflowName) {
            $workflow = $this->getWorkflow($workflowName);
            if (
                null !== $workflow
                && \in_array($this->groupName, $workflow->getDefinition()->getExclusiveRecordGroups(), true)
            ) {
                return true;
            }
        }

        return false;
    }

    private function getWorkflow(string $workflowName): ?Workflow
    {
        try {
            return $this->workflowManager->getWorkflow($workflowName);
        } catch (WorkflowException) {
            return null;
        }
    }
}
