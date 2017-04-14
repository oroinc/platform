<?php

namespace Oro\Bundle\WorkflowBundle\Processor\Context;

use Oro\Component\ChainProcessor\ContextInterface;

trait ValidateTransitionContextTrait
{
    /**
     * @param ContextInterface $context
     * @throws \InvalidArgumentException
     */
    private function validateContextType(ContextInterface $context)
    {
        if (!$context instanceof TransitionContext) {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s supports only %s context, but %s given.',
                    static::class,
                    TransitionContext::class,
                    get_class($context)
                )
            );
        }
    }
}
