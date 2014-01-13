<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Bundle\WorkflowBundle\Model\Process;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Model\Action\ActionAssembler;

class ProcessFactory
{
    /**
     * @var ActionAssembler
     */
    protected $actionAssembler;

    /**
     * @param ActionAssembler $actionAssembler
     */
    public function __construct(ActionAssembler $actionAssembler)
    {
        $this->actionAssembler = $actionAssembler;
    }

    /**
     * Create process instance.
     *
     * @param ProcessDefinition $processDefinition
     * @return Process
     */
    public function create(ProcessDefinition $processDefinition)
    {
        return new Process($this->actionAssembler, $processDefinition);
    }
}
