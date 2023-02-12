<?php

namespace Oro\Component\ChainProcessor\Debug;

use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\ChainProcessor\ProcessorApplicableCheckerFactoryInterface;

/**
 * Decorates a chain applicable checker factory to decorate created chain applicable checker
 * with TraceableChainApplicableChecker class that logs information about execution of child checkers.
 */
class TraceableProcessorApplicableCheckerFactory implements ProcessorApplicableCheckerFactoryInterface
{
    private ProcessorApplicableCheckerFactoryInterface $applicableCheckerFactory;
    private TraceLogger $logger;

    public function __construct(
        ProcessorApplicableCheckerFactoryInterface $applicableCheckerFactory,
        TraceLogger $logger
    ) {
        $this->applicableCheckerFactory = $applicableCheckerFactory;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function createApplicableChecker(): ChainApplicableChecker
    {
        $traceableChecker = new TraceableChainApplicableChecker($this->logger);
        $innerChecker = $this->applicableCheckerFactory->createApplicableChecker();
        foreach ($innerChecker as $checker) {
            $traceableChecker->addChecker($checker);
        }

        return $traceableChecker;
    }
}
