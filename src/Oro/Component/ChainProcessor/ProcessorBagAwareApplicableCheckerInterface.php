<?php

namespace Oro\Component\ChainProcessor;

/**
 * This interface should be implemented by applicable checkers that depends on the ProcessorBag.
 */
interface ProcessorBagAwareApplicableCheckerInterface
{
    public function setProcessorBag(ProcessorBagInterface $processorBag): void;
}
