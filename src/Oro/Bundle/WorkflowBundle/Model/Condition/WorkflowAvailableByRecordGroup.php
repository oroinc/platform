<?php

namespace Oro\Bundle\WorkflowBundle\Model\Condition;

use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

/**
 * Checks whether an entity has an applicable workflow in a specified exclusive record group.
 */
class WorkflowAvailableByRecordGroup extends AbstractCondition
{
    private string $groupName;
    private string $entityClass;

    public function __construct(
        private readonly WorkflowManager $workflowManager
    ) {
    }

    #[\Override]
    public function getName(): string
    {
        return 'workflow_available_by_record_group';
    }

    #[\Override]
    public function initialize(array $options): self
    {
        if (empty($options['group_name'])) {
            throw new InvalidArgumentException('Group name parameter is required');
        }
        if (empty($options['entity_class'])) {
            throw new InvalidArgumentException('Entity class parameter is required');
        }

        $this->groupName = $options['group_name'];
        $this->entityClass = $options['entity_class'];

        return $this;
    }

    #[\Override]
    protected function isConditionAllowed(mixed $context): bool
    {
        $workflows = $this->workflowManager->getApplicableWorkflows($this->entityClass);
        foreach ($workflows as $workflow) {
            if (\in_array($this->groupName, $workflow->getDefinition()->getExclusiveRecordGroups(), true)) {
                return true;
            }
        }

        return false;
    }
}
