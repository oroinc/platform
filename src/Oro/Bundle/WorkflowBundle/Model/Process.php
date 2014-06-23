<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Model\Action\ActionAssembler;
use Oro\Bundle\WorkflowBundle\Model\Action\ActionInterface;

class Process
{
    /**
     * @var ActionAssembler
     */
    protected $actionAssembler;

    /**
     * @var ProcessDefinition
     */
    protected $processDefinition;

    /**
     * @var ActionInterface
     */
    protected $action;

    /**
     * @param ActionAssembler $actionAssembler
     * @param ProcessDefinition $processDefinition
     */
    public function __construct(ActionAssembler $actionAssembler, ProcessDefinition $processDefinition)
    {
        $this->actionAssembler = $actionAssembler;
        $this->processDefinition = $processDefinition;
    }

    protected function getAction()
    {
        if (!$this->action) {
            $this->action = $this->actionAssembler->assemble($this->processDefinition->getActionsConfiguration());
        }

        return $this->action;
    }

    /**
     * @param mixed $context
     */
    public function execute($context)
    {
        $this->getAction()->execute($context);
    }
}
