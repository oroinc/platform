<?php

namespace Oro\Component\ChainProcessor;

/**
 * Provides an interface for different kind of processor factories.
 */
interface ProcessorFactoryInterface
{
    /**
     * Gets a processor by its identifier
     *
     * @param string $processorId
     *
     * @return ProcessorInterface|null
     */
    public function getProcessor($processorId);
}
