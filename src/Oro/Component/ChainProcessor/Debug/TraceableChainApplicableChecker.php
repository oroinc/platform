<?php

namespace Oro\Component\ChainProcessor\Debug;

use Oro\Component\ChainProcessor\ApplicableCheckerInterface;
use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Decorates a chain applicable checker to log information about execution of child checkers.
 */
class TraceableChainApplicableChecker extends ChainApplicableChecker
{
    private TraceLogger $logger;

    public function __construct(TraceLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    protected function executeChecker(
        ApplicableCheckerInterface $checker,
        ContextInterface $context,
        array $processorAttributes
    ): int {
        $this->logger->startApplicableChecker(\get_class($checker));
        $result = $checker->isApplicable($context, $processorAttributes);
        $this->logger->stopApplicableChecker();

        return $result;
    }
}
