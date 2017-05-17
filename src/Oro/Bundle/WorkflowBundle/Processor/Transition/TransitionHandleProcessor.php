<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Performs regular transition processing (workflow->transit)
 */
class TransitionHandleProcessor implements ProcessorInterface
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

        $transition = $context->getTransition();
        $workflowItem = $context->getWorkflowItem();

        try {
            $this->workflowManager->transit($workflowItem, $transition);
        } catch (\Exception $exception) {
            $context->setError($exception);
            $context->setFirstGroup('normalize');
        }
    }
}
