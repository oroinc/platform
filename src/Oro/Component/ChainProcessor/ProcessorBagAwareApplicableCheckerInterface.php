<?php

namespace Oro\Component\ChainProcessor;

/**
 * This interface should be implemented by applicable checkers that depends on the ProcessorBag.
 */
interface ProcessorBagAwareApplicableCheckerInterface
{
    /**
     * Sets the ProcessorBag.
     *
     * @param ProcessorBagInterface|null $processorBag The ProcessorBagInterface instance or null
     */
    public function setProcessorBag(ProcessorBagInterface $processorBag = null);
}
