<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ContextInterface as ComponentContextInterface;
use Oro\Component\ChainProcessor\ProcessorApplicableCheckerFactoryInterface;
use Oro\Component\ChainProcessor\ProcessorBag;
use Oro\Component\ChainProcessor\ProcessorBagConfigProviderInterface;
use Oro\Component\ChainProcessor\ProcessorIterator;
use Oro\Component\ChainProcessor\ProcessorIteratorFactoryInterface;
use Oro\Component\ChainProcessor\ProcessorRegistryInterface;

/**
 * By performance reasons this processor bag uses different iterators and applicable checkers
 * optimized for actions with and without groups.
 */
class OptimizedProcessorBag extends ProcessorBag
{
    private ProcessorIteratorFactoryInterface $ungroupedProcessorIteratorFactory;

    public function __construct(
        ProcessorBagConfigProviderInterface $configProvider,
        ProcessorRegistryInterface $processorRegistry,
        bool $debug,
        ProcessorApplicableCheckerFactoryInterface $applicableCheckerFactory,
        ProcessorIteratorFactoryInterface $processorIteratorFactory,
        ProcessorIteratorFactoryInterface $ungroupedProcessorIteratorFactory
    ) {
        parent::__construct(
            $configProvider,
            $processorRegistry,
            $debug,
            $applicableCheckerFactory,
            $processorIteratorFactory
        );
        $this->ungroupedProcessorIteratorFactory = $ungroupedProcessorIteratorFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function createProcessorIterator(ComponentContextInterface $context): ProcessorIterator
    {
        $action = $context->getAction();

        $processorIteratorFactory = $this->getActionGroups($action)
            ? $this->processorIteratorFactory
            : $this->ungroupedProcessorIteratorFactory;

        return $processorIteratorFactory->createProcessorIterator(
            $this->getActionProcessors($action),
            $context,
            $this->processorApplicableChecker,
            $this->processorRegistry
        );
    }
}
