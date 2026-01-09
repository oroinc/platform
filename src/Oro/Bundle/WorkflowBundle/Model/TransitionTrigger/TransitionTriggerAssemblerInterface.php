<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger;

use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

/**
 * Defines the contract for assembling transition triggers from configuration options.
 *
 * Implementations of this interface are responsible for creating specific types of transition triggers
 * (such as cron or event-based triggers) from configuration arrays. Each assembler determines whether
 * it can handle a given configuration and creates the appropriate trigger instance.
 */
interface TransitionTriggerAssemblerInterface
{
    /**
     * @param array $options
     * @return bool
     */
    public function canAssemble(array $options);

    /**
     * @param array $options
     * @param string $transitionName
     * @param WorkflowDefinition $workflowDefinition
     * @return BaseTransitionTrigger
     */
    public function assemble(array $options, $transitionName, WorkflowDefinition $workflowDefinition);
}
