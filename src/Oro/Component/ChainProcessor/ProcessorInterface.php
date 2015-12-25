<?php

namespace Oro\Component\ChainProcessor;

/**
 * This interface should be implemented by all processors.
 */
interface ProcessorInterface
{
    /**
     * Does a work based on the given context.
     * Each processor should check the context, does appropriate work and put results to the context.
     *
     * @param ContextInterface $context
     */
    public function process(ContextInterface $context);
}
