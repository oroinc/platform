<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger\Stub;

use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\AbstractTransitionTriggerAssembler;

class AbstractTransitionTriggerAssemblerStub extends AbstractTransitionTriggerAssembler
{
    /** @var bool */
    private $canAssemble;

    /**
     * @param bool $canAssemble
     */
    public function __construct($canAssemble = true)
    {
        $this->canAssemble = $canAssemble;
    }

    #[\Override]
    public function canAssemble(array $options)
    {
        return $this->canAssemble;
    }

    #[\Override]
    protected function verifyTrigger(BaseTransitionTrigger $trigger)
    {
        //void
    }

    #[\Override]
    protected function assembleTrigger(array $options, WorkflowDefinition $workflowDefinition)
    {
        return new TriggerStub();
    }

    /**
     * @param AbstractTransitionTriggerAssembler $assembler
     * @param BaseTransitionTrigger $trigger .
     */
    public function verifyProxy(AbstractTransitionTriggerAssembler $assembler, BaseTransitionTrigger $trigger)
    {
        $assembler->verifyTrigger($trigger);
    }
}
