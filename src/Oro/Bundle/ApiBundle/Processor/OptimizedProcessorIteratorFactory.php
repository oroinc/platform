<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ApplicableCheckerInterface;
use Oro\Component\ChainProcessor\ContextInterface as ComponentContextInterface;
use Oro\Component\ChainProcessor\ProcessorBagAwareIteratorFactoryInterface;
use Oro\Component\ChainProcessor\ProcessorBagInterface;
use Oro\Component\ChainProcessor\ProcessorFactoryInterface;
use Oro\Component\ChainProcessor\ProcessorIteratorFactoryInterface;

class OptimizedProcessorIteratorFactory implements
    ProcessorIteratorFactoryInterface,
    ProcessorBagAwareIteratorFactoryInterface
{
    /** @var ProcessorBagInterface|null */
    protected $processorBag;

    /**
     * {@inheritdoc}
     */
    public function createProcessorIterator(
        array $processors,
        ComponentContextInterface $context,
        ApplicableCheckerInterface $applicableChecker,
        ProcessorFactoryInterface $processorFactory
    ) {
        return new OptimizedProcessorIterator(
            $processors,
            $this->processorBag->getActionGroups($context->getAction()),
            $context,
            $applicableChecker,
            $processorFactory
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setProcessorBag(ProcessorBagInterface $processorBag = null)
    {
        $this->processorBag = $processorBag;
    }
}
