<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger;

use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Exception\TransitionTriggerVerifierException;

/**
 * Provides common functionality for transition trigger assemblers.
 *
 * This abstract class implements the core logic for assembling transition triggers from configuration options.
 * It handles validation of options, creation of trigger instances, and verification of assembled triggers.
 * Subclasses must implement the assembleTrigger() and verifyTrigger() methods to provide specific trigger assembly
 * and validation logic for different trigger types.
 */
abstract class AbstractTransitionTriggerAssembler implements TransitionTriggerAssemblerInterface
{
    /**
     * @param array $options
     * @param WorkflowDefinition $workflowDefinition
     * @return BaseTransitionTrigger
     */
    abstract protected function assembleTrigger(array $options, WorkflowDefinition $workflowDefinition);

    /**
     * @throws TransitionTriggerVerifierException
     */
    abstract protected function verifyTrigger(BaseTransitionTrigger $trigger);

    /**
     * @throws \InvalidArgumentException
     */
    #[\Override]
    public function assemble(array $options, $transitionName, WorkflowDefinition $workflowDefinition)
    {
        if (false === $this->canAssemble($options)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Can not assemble trigger for transition %s in workflow %s by provided options %s.',
                    $transitionName,
                    $workflowDefinition->getName(),
                    var_export($options, 1)
                )
            );
        }

        $trigger = $this->assembleTrigger($options, $workflowDefinition);

        $trigger->setWorkflowDefinition($workflowDefinition)
            ->setTransitionName($transitionName)
            ->setQueued($this->getOption($options, 'queued', true));

        $this->verifyTrigger($trigger);

        return $trigger;
    }

    /**
     * @param array $options
     * @param string $optionKey
     * @param mixed $defaultValue
     * @return mixed
     */
    protected function getOption(array $options, $optionKey, $defaultValue = null)
    {
        return array_key_exists($optionKey, $options) ? $options[$optionKey] : $defaultValue;
    }
}
