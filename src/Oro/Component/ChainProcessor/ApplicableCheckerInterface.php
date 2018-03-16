<?php

namespace Oro\Component\ChainProcessor;

/**
 * Provides an interface that should be implemented by classes that is used to check
 * whether a processor should be executed in the current execution context.
 */
interface ApplicableCheckerInterface
{
    public const APPLICABLE     = 1;
    public const ABSTAIN        = 0;
    public const NOT_APPLICABLE = -1;

    /**
     * Checks whether a processor can be executed in the given context.
     *
     * This method must return one of the following constants:
     * APPLICABLE, ABSTAIN, or NOT_APPLICABLE.
     *
     * @param ContextInterface $context
     * @param array            $processorAttributes
     *
     * @return int Either APPLICABLE, ABSTAIN, or NOT_APPLICABLE
     */
    public function isApplicable(ContextInterface $context, array $processorAttributes);
}
