<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger;

use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class TriggersBag
{
    /**
     * @var WorkflowDefinition
     */
    private $definition;

    /**
     * @var array|BaseTransitionTrigger[]
     */
    private $triggers = [];

    /**
     * @param WorkflowDefinition $definition
     * @param array|BaseTransitionTrigger[] $triggers
     */
    public function __construct(WorkflowDefinition $definition, array $triggers)
    {
        $this->definition = $definition;
        foreach ($triggers as $trigger) {
            $this->addTrigger($trigger);
        }
    }

    /**
     * @param BaseTransitionTrigger $trigger
     */
    protected function addTrigger(BaseTransitionTrigger $trigger)
    {
        $this->triggers[] = $trigger;
    }

    /**
     * @return array|\Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger[]
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
