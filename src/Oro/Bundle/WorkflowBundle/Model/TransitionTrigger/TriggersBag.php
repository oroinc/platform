<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger;

use Oro\Bundle\WorkflowBundle\Entity\AbstractTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class TriggersBag
{
    /**
     * @var WorkflowDefinition
     */
    private $definition;

    /**
     * @var array|AbstractTransitionTrigger[]
     */
    private $triggers = [];

    /**
     * @param WorkflowDefinition $definition
     * @param array|AbstractTransitionTrigger[] $triggers
     */
    public function __construct(WorkflowDefinition $definition, array $triggers)
    {
        $this->definition = $definition;
        foreach ($triggers as $trigger) {
            //verify trigger instance
            $this->addTrigger($trigger);
        }
    }

    /**
     * @param AbstractTransitionTrigger $trigger
     */
    protected function addTrigger(AbstractTransitionTrigger $trigger)
    {
        $trigger->setWorkflowDefinition($this->definition);
        $this->triggers[] = $trigger;
    }

    /**
     * @return array|\Oro\Bundle\WorkflowBundle\Entity\AbstractTransitionTrigger[]
     */
    public function getTriggers()
    {
        return $this->triggers;
    }

    /**
     * @return WorkflowDefinition
     */
    public function getDefinition()
    {
        return $this->definition;
    }
}
