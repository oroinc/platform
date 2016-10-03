<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger\Stub;

use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\AbstractTransitionTriggerAssembler;

class AbstractTransitionTriggerAssemblerStub extends AbstractTransitionTriggerAssembler
{
    /**
     * @var bool
     */
    private $canAssemble;

    /**
     * @param bool $canAssemble
     */
    public function __construct($canAssemble = true)
    {
        $this->canAssemble = $canAssemble;
    }

    /**
     * {@inheritdoc}
     */
    public function canAssemble(array $options)
    {
        return $this->canAssemble;
    }

    /**
     * @param BaseTransitionTrigger $trigger
     */
    protected function verifyTrigger(BaseTransitionTrigger $trigger)
    {
        //void
    }

    /**
     * {@inheritdoc}
     */
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
