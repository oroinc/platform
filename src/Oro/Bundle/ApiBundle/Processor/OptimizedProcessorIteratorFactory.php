<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ApplicableCheckerInterface;
use Oro\Component\ChainProcessor\ContextInterface as ComponentContextInterface;
use Oro\Component\ChainProcessor\ProcessorBagAwareIteratorFactoryInterface;
use Oro\Component\ChainProcessor\ProcessorBagInterface;
use Oro\Component\ChainProcessor\ProcessorIteratorFactoryInterface;
use Oro\Component\ChainProcessor\ProcessorRegistryInterface;

/**
 * The factory to create an instance of OptimizedProcessorIterator class.
 */
class OptimizedProcessorIteratorFactory implements
    ProcessorIteratorFactoryInterface,
    ProcessorBagAwareIteratorFactoryInterface
{
    /** @var ProcessorBagInterface|null */
    private $processorBag;

    /**
     * {@inheritdoc}
     */
    public function createProcessorIterator(
        array $processors,
        ComponentContextInterface $context,
        ApplicableCheckerInterface $applicableChecker,
        ProcessorRegistryInterface $processorRegistry
    ) {
        return new OptimizedProcessorIterator(
            $processors,
            $this->processorBag->getActionGroups($context->getAction()),
            $context,
            $applicableChecker,
            $processorRegistry
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
