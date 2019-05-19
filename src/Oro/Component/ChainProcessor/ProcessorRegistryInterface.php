<?php

namespace Oro\Component\ChainProcessor;

/**
 * Provides an interface for processor registries
 * that are used to get an instance of a processor by its identifier.
 */
interface ProcessorRegistryInterface
{
    /**
     * Gets a processor by its identifier.
     *
     * @param string $processorId
     *
     * @return ProcessorInterface
     */
    public function getProcessor(string $processorId): ProcessorInterface;
}
