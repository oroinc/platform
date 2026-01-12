<?php

namespace Oro\Bundle\WorkflowBundle\Model\Action;

use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Retrieves an available workflow for a given entity and record group.
 *
 * This action searches through all applicable workflows for the specified entity class
 * and returns the first workflow that belongs to the given exclusive record group.
 * The result is stored in the specified attribute for use in subsequent workflow operations.
 */
class GetAvailableWorkflowByRecordGroup extends AbstractAction
{
    /** @var WorkflowManager */
    protected $manager;

    /** @var array */
    protected $options;

    public function __construct(ContextAccessor $contextAccessor, WorkflowManager $manager)
    {
        parent::__construct($contextAccessor);

        $this->manager = $manager;
    }

    #[\Override]
    public function initialize(array $options)
    {
        if (empty($options['group_name'])) {
            throw new InvalidParameterException('Group name parameter is required');
        }

        if (empty($options['entity_class'])) {
            throw new InvalidParameterException('Entity class parameter is required');
        }

        if (empty($options['attribute'])) {
            throw new InvalidParameterException('Attribute parameter is required');
        }

        if (!$options['attribute'] instanceof PropertyPathInterface) {
            throw new InvalidParameterException('Attribute must be valid property');
        }

        $this->options = $options;

        return $this;
    }

    #[\Override]
    protected function executeAction($context)
    {
        $entityClass = $this->contextAccessor->getValue($context, $this->options['entity_class']);
        $groupName = $this->contextAccessor->getValue($context, $this->options['group_name']);

        $availableWorkflow = null;
        $workflows = $this->manager->getApplicableWorkflows($entityClass);
        foreach ($workflows as $workflow) {
            if (in_array($groupName, $workflow->getDefinition()->getExclusiveRecordGroups(), true)) {
                $availableWorkflow = $workflow;
                break;
            }
        }

        $this->contextAccessor->setValue($context, $this->options['attribute'], $availableWorkflow);
    }
}
