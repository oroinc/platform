<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Bundle\ActionBundle\Model\AttributeManager;

class VariableManager extends AttributeManager
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

    /**
     * @param VariableAssembler $variableAssembler
     */
    public function setVariableAssembler(VariableAssembler $variableAssembler)
    {
        $this->variableAssembler = $variableAssembler;
    }
}
