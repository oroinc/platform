<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger\Stub;

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
    public function __construct($canAssemble)
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
     * {@inheritdoc}
     */
    protected function assembleTrigger(array $options, WorkflowDefinition $workflowDefinition)
    {
        return new TriggerStub();
    }
}
