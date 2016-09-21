<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\AbstractTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Exception\AssemblerException;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TransitionTriggerAssemblerInterface;

class WorkflowTransitionTriggersAssembler
{
    /** @var TransitionTriggerAssemblerInterface[] */
    private $assemblers = [];

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @return AbstractTransitionTrigger[]
     * @throws AssemblerException
     */
    public function assembleTriggers(WorkflowDefinition $workflowDefinition)
    {
        $configuration = $workflowDefinition->getConfiguration();

        /**@var array $transitions */
        $transitions = array_key_exists(WorkflowConfiguration::NODE_TRANSITIONS, $configuration) ?
            $configuration[WorkflowConfiguration::NODE_TRANSITIONS] : [];

        $triggers = [];

        foreach ($transitions as $transitionName => $transition) {
            /**
             * @var array $transition
             * @var array $triggersOptions
             */
            $triggersOptions = array_key_exists(WorkflowConfiguration::NODE_TRANSITION_TRIGGERS, $transition) ?
                $transition[WorkflowConfiguration::NODE_TRANSITION_TRIGGERS] : [];

            foreach ($triggersOptions as $options) {
                $triggers[] = $this->assemble($options, $transitionName, $workflowDefinition);
            }
        }

        return $triggers;
    }

    /**
     * @param array $options
     * @param string $transitionName
     * @param WorkflowDefinition $workflowDefinition
     * @return AbstractTransitionTrigger
     * @throws AssemblerException
     */
    private function assemble(array $options, $transitionName, WorkflowDefinition $workflowDefinition)
    {
        foreach ($this->assemblers as $assembler) {
            if ($assembler->canAssemble($options)) {
                return $assembler->assemble($options, $transitionName, $workflowDefinition);
            }
        }

        throw new AssemblerException(
            sprintf(
                'Can\'t assemble trigger for %s workflow in transition %s by given options: %s',
                $workflowDefinition->getName(),
                $transitionName,
                var_export($options, 1)
            )
        );
    }

    /**
     * @param TransitionTriggerAssemblerInterface $assembler
     */
    public function registerAssembler(TransitionTriggerAssemblerInterface $assembler)
    {
        $this->assemblers[] = $assembler;
    }
}
