<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Model\Action\ActionAssembler;

use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

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

    /**
     * @param ActionAssembler $actionAssembler
     * @param ConditionFactory $conditionFactory
     */
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
