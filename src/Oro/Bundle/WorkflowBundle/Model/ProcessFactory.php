<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Component\Action\Action\ActionAssembler;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

/**
 * Factory for creating process instances from process definitions.
 *
 * This factory creates {@see Process} objects configured with action and condition assemblers,
 * enabling the execution of process definitions.
 */
class ProcessFactory
{
    /**
     * @var ActionAssembler
     */
    protected $actionAssembler;

    /**
     * @var ConditionFactory
     */
    protected $conditionFactory;

    public function __construct(ActionAssembler $actionAssembler, ConditionFactory $conditionFactory)
    {
        $this->actionAssembler = $actionAssembler;
        $this->conditionFactory = $conditionFactory;
    }

    /**
     * Create process instance.
     *
     * @param ProcessDefinition $processDefinition
     * @return Process
     */
    public function create(ProcessDefinition $processDefinition)
    {
        return new Process($this->actionAssembler, $this->conditionFactory, $processDefinition);
    }
}
