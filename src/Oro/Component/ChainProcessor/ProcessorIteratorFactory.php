<?php

namespace Oro\Component\ChainProcessor;

class ProcessorIteratorFactory implements ProcessorIteratorFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createProcessorIterator(
        array $processors,
        ContextInterface $context,
        ApplicableCheckerInterface $applicableChecker,
        ProcessorFactoryInterface $processorFactory
    ) {
        return new ProcessorIterator($processors, $context, $applicableChecker, $processorFactory);
    }
}
