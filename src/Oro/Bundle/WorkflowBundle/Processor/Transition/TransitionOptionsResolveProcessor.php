<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Transition;

use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Bundle\WorkflowBundle\Resolver\TransitionOptionsResolver;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class TransitionOptionsResolveProcessor implements ProcessorInterface
{
    /** @var TransitionOptionsResolver */
    private $transitionOptionsResolver;

    /**
     * @param TransitionOptionsResolver $transitionOptionsResolver
     */
    public function __construct(TransitionOptionsResolver $transitionOptionsResolver)
    {
        $this->transitionOptionsResolver = $transitionOptionsResolver;
    }

    /**
     * @param ContextInterface|TransitionContext $context
     */
    public function process(ContextInterface $context)
    {
        $this->transitionOptionsResolver
            ->resolveTransitionOptions($context->getTransition(), $context->getWorkflowItem());
    }
}
