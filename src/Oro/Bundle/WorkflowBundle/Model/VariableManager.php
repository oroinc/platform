<?php

namespace Oro\Bundle\WorkflowBundle\Model;

/**
 * Manages workflow variables and their assembly.
 *
 * This manager provides access to the variable assembler for creating and configuring
 * workflow variables from configuration data.
 */
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
