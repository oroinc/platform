<?php

namespace Oro\Component\ChainProcessor;

/**
 * The factory to create an instance of ProcessorIterator class.
 */
class ProcessorIteratorFactory implements ProcessorIteratorFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createProcessorIterator(
        array $processors,
        ContextInterface $context,
        ApplicableCheckerInterface $applicableChecker,
        ProcessorRegistryInterface $processorRegistry
    ): ProcessorIterator {
        return new ProcessorIterator($processors, $context, $applicableChecker, $processorRegistry);
    }
}
