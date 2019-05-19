<?php

namespace Oro\Component\ChainProcessor;

/**
 * Represents a factory to create an instance of ProcessorIterator class.
 */
interface ProcessorIteratorFactoryInterface
{
    /**
     * Creates an object that can be used to iterator through processors.
     *
     * @param array                      $processors
     * @param ContextInterface           $context
     * @param ApplicableCheckerInterface $applicableChecker
     * @param ProcessorRegistryInterface $processorRegistry
     *
     * @return ProcessorIterator
     */
    public function createProcessorIterator(
        array $processors,
        ContextInterface $context,
        ApplicableCheckerInterface $applicableChecker,
        ProcessorRegistryInterface $processorRegistry
    );
}
