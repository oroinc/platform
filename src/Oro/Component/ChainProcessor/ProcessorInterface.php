<?php

namespace Oro\Component\ChainProcessor;

/**
 * This interface should be implemented by all processors.
 */
interface ProcessorInterface
{
    /**
     * @param ContextInterface $context
     */
    public function process(ContextInterface $context);
}
