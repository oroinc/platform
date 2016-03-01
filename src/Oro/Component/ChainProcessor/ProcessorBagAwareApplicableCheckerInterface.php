<?php

namespace Oro\Component\ChainProcessor;

/**
 * This interface should be implemented by applicable checkers that depends on a ProcessorBag.
 */
interface ProcessorBagAwareApplicableCheckerInterface
{
    /**
     * Sets the ProcessorBag.
     *
     * @param ProcessorBagInterface|null $processorBag A ProcessorBagInterface instance or null
     */
    public function setProcessorBag(ProcessorBagInterface $processorBag = null);
}
