<?php

namespace Oro\Component\ChainProcessor;

/**
 * Provides an interface that should be implemented by all applicable checker classes.
 */
interface ApplicableCheckerInterface
{
    const APPLICABLE = 1;
    const ABSTAIN = 0;
    const NOT_APPLICABLE = -1;

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
