<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ApplicableCheckerInterface;
use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\ChainProcessor\ContextInterface as ComponentContextInterface;
use Oro\Component\ChainProcessor\ProcessorApplicableCheckerFactoryInterface;
use Oro\Component\ChainProcessor\ProcessorBag;
use Oro\Component\ChainProcessor\ProcessorBagConfigProviderInterface;
use Oro\Component\ChainProcessor\ProcessorFactoryInterface;
use Oro\Component\ChainProcessor\ProcessorIteratorFactoryInterface;

/**
 * By performance reasons this processor bag uses different iterators and applicable checkers
 * optimized for actions with and without groups.
 */
class OptimizedProcessorBag extends ProcessorBag
{
    /** @var ProcessorApplicableCheckerFactoryInterface */
    protected $ungroupedApplicableCheckerFactory;

    /** @var ProcessorIteratorFactoryInterface */
    protected $ungroupedProcessorIteratorFactory;

    /** @var ChainApplicableChecker|null */
    protected $ungroupedProcessorApplicableChecker;

    /**
     * @param ProcessorBagConfigProviderInterface        $configProvider
     * @param ProcessorFactoryInterface                  $processorFactory
     * @param bool                                       $debug
     * @param ProcessorApplicableCheckerFactoryInterface $applicableCheckerFactory
     * @param ProcessorIteratorFactoryInterface          $processorIteratorFactory
     * @param ProcessorApplicableCheckerFactoryInterface $ungroupedApplicableCheckerFactory
     * @param ProcessorIteratorFactoryInterface          $ungroupedProcessorIteratorFactory
     */
    public function __construct(
        ProcessorBagConfigProviderInterface $configProvider,
        ProcessorFactoryInterface $processorFactory,
        $debug,
        ProcessorApplicableCheckerFactoryInterface $applicableCheckerFactory,
        ProcessorIteratorFactoryInterface $processorIteratorFactory,
        ProcessorApplicableCheckerFactoryInterface $ungroupedApplicableCheckerFactory,
        ProcessorIteratorFactoryInterface $ungroupedProcessorIteratorFactory
    ) {
        parent::__construct(
            $configProvider,
            $processorFactory,
            $debug,
            $applicableCheckerFactory,
            $processorIteratorFactory
        );
        $this->ungroupedApplicableCheckerFactory = $ungroupedApplicableCheckerFactory;
        $this->ungroupedProcessorIteratorFactory = $ungroupedProcessorIteratorFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function addApplicableChecker(ApplicableCheckerInterface $checker, $priority = 0)
    {
        parent::addApplicableChecker($checker, $priority);
        $this->ungroupedProcessorApplicableChecker = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeProcessorApplicableChecker()
    {
        parent::initializeProcessorApplicableChecker();
        $this->ungroupedProcessorApplicableChecker =
            $this->ungroupedApplicableCheckerFactory->createApplicableChecker();
        $this->initializeApplicableChecker($this->ungroupedProcessorApplicableChecker);
    }

    /**
     * {@inheritdoc}
     */
    protected function createProcessorIterator(ComponentContextInterface $context)
    {
        $action = $context->getAction();
        $actionProcessors = [];
        $processors = $this->configProvider->getProcessors();
        if (!empty($processors[$action])) {
            $actionProcessors = $processors[$action];
        }

        $processorIteratorFactory = $this->processorIteratorFactory;
        $processorApplicableChecker = $this->processorApplicableChecker;
        $groups = $this->getActionGroups($action);
        if (empty($groups)) {
            $processorIteratorFactory = $this->ungroupedProcessorIteratorFactory;
            $processorApplicableChecker = $this->ungroupedProcessorApplicableChecker;
        }

        return $processorIteratorFactory->createProcessorIterator(
            $actionProcessors,
            $context,
            $processorApplicableChecker,
            $this->processorFactory
        );
    }
}
