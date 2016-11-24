<?php

namespace Oro\Bundle\WorkflowBundle\Model\Condition;

use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

class WorkflowAvailableByRecordGroup extends AbstractCondition
{
    const NAME = 'workflow_available_by_record_group';

    /** @var WorkflowManager */
    protected $manager;

    /** @var array */
    protected $options;

    /**
     * @param WorkflowManager $manager
     */
    public function __construct(WorkflowManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options['group_name'])) {
            throw new InvalidArgumentException('Group name parameter is required');
        }

        if (empty($options['entity_class'])) {
            throw new InvalidArgumentException('Entity class parameter is required');
        }

        $this->options = $options;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        $workflows = $this->manager->getApplicableWorkflows($this->options['entity_class']);
        foreach ($workflows as $workflow) {
            if (in_array($this->options['group_name'], $workflow->getDefinition()->getExclusiveRecordGroups(), true)) {
                return true;
            }
        }

        return false;
    }
}
