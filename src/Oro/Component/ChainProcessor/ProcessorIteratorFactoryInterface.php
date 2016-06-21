<?php

namespace Oro\Component\ChainProcessor;

interface ProcessorIteratorFactoryInterface
{
    /**
     * Creates an object that can be used to iterator through processors.
     *
     * @param array                      $processors
     * @param ContextInterface           $context
     * @param ApplicableCheckerInterface $applicableChecker
     * @param ProcessorFactoryInterface  $processorFactory
     *
     * @return ProcessorIterator
     */
    public function createProcessorIterator(
        array $processors,
        ContextInterface $context,
        ApplicableCheckerInterface $applicableChecker,
        ProcessorFactoryInterface $processorFactory
    );
}
