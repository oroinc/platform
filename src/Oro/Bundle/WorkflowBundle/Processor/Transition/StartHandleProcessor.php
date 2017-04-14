<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Handles start workflow
 */
class StartHandleProcessor implements ProcessorInterface
{
    /** @var WorkflowManager */
    private $workflowManager;

    /**
     * @param WorkflowManager $workflowManager
     */
    public function __construct(WorkflowManager $workflowManager)
    {
        $this->workflowManager = $workflowManager;
    }

    /**
     * @param ContextInterface|TransitionContext $context
     */
    public function process(ContextInterface $context)
    {
        if (!$context->isSaved() || $context->hasError()) {
            return;
        }

        try {
            $data = $context->get(TransitionContext::INIT_DATA) ?: [];
            $workflowItem = $this->workflowManager->startWorkflow(
                $context->getWorkflowName(),
                $context->getWorkflowItem()->getEntity(),
                $context->getTransition(),
                $data
            );

            //replace workflowItem with newly created one
            $context->setWorkflowItem($workflowItem);
        } catch (\Throwable $error) {
            $context->setError($error);
            $context->setFirstGroup('normalize');
        }
    }
}
