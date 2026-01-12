<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Resolver\TransitionOptionsResolver;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Resolves dynamic transition options based on workflow item state.
 *
 * This processor uses the transition options resolver to evaluate and resolve any dynamic options
 * configured for the transition. Dynamic options may depend on the current state of the workflow item,
 * entity data, or other runtime conditions. The resolver updates the transition with the resolved options,
 * which are then used by subsequent processors for form creation and rendering.
 */
class TransitionOptionsResolveProcessor implements ProcessorInterface
{
    /** @var TransitionOptionsResolver */
    private $transitionOptionsResolver;

    public function __construct(TransitionOptionsResolver $transitionOptionsResolver)
    {
        $this->transitionOptionsResolver = $transitionOptionsResolver;
    }

    /**
     * @param ContextInterface|TransitionContext $context
     */
    #[\Override]
    public function process(ContextInterface $context)
    {
        $this->transitionOptionsResolver
            ->resolveTransitionOptions($context->getTransition(), $context->getWorkflowItem());
    }
}
