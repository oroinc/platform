<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger\Stub;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\TriggerAbstractAssembler;

class TriggerAbstractAssemblerStub extends TriggerAbstractAssembler
{
    /**
     * @var bool
     */
    private $canAssemble;

    /**
     * @param bool $canAssemble
     */
    public function __construct($canAssemble)
    {
        $this->canAssemble = $canAssemble;
    }

    public function canAssemble(array $options)
    {
        return $this->canAssemble;
    }

    protected function assembleTrigger(array $options, WorkflowDefinition $workflowDefinition)
    {
        return new TriggerStub();
    }
}
