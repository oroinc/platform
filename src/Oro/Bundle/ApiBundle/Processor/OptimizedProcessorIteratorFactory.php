<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ApplicableCheckerInterface;
use Oro\Component\ChainProcessor\ContextInterface as ComponentContextInterface;
use Oro\Component\ChainProcessor\ProcessorFactoryInterface;
use Oro\Component\ChainProcessor\ProcessorIteratorFactoryInterface;

class OptimizedProcessorIteratorFactory implements ProcessorIteratorFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createProcessorIterator(
        array $processors,
        ComponentContextInterface $context,
        ApplicableCheckerInterface $applicableChecker,
        ProcessorFactoryInterface $processorFactory
    ) {
        return new OptimizedProcessorIterator($processors, $context, $applicableChecker, $processorFactory);
    }
}
