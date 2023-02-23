<?php

namespace Oro\Component\ChainProcessor;

/**
 * This interface should be implemented by processor iterator factory that depends on a ProcessorBag.
 */
interface ProcessorBagAwareIteratorFactoryInterface
{
    public function setProcessorBag(ProcessorBagInterface $processorBag): void;
}
