<?php

namespace Oro\Bundle\WorkflowBundle\Model;

class VariableManager
{
    /**
     * @var VariableAssembler
     */
    protected $variableAssembler;

    /**
     * @return VariableAssembler
     */
    public function getVariableAssembler()
    {
        return $this->variableAssembler;
    }

    public function setVariableAssembler(VariableAssembler $variableAssembler)
    {
        $this->variableAssembler = $variableAssembler;
    }
}
