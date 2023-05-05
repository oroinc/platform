<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Initializes base context attributes.
 */
class BaseContextInitProcessor implements ProcessorInterface
{
    private WorkflowManager $workflowManager;

    public function __construct(WorkflowManager $workflowManager)
    {
        $this->workflowManager = $workflowManager;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var TransitionContext $context */

        $transitionName = $context->getTransitionName();

        try {
            $workflow = $this->workflowManager->getWorkflow($context->getWorkflowName());
            $transition = $workflow->getTransitionManager()->extractTransition($transitionName);
        } catch (WorkflowException $exception) {
            $context->setError($exception);
            $context->setFirstGroup('normalize');

            return;
        }

        //configure context
        $context->setWorkflow($workflow);
        $context->setTransition($transition);

        //set up start transition context options
        //no workflowItem means that it is start transition process initialized
        if (!$context->hasWorkflowItem()) {
            $context->setIsStartTransition(true); //defaults false
            $context->set(TransitionContext::HAS_INIT_OPTIONS, !$transition->isEmptyInitOptions());
        }

        $context->setIsCustomForm(
            $transition->hasFormConfiguration() && $context->getResultType()->supportsCustomForm()
        );
    }
}
