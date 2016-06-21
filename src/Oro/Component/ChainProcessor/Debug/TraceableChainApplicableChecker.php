<?php

namespace Oro\Component\ChainProcessor\Debug;

use Oro\Component\ChainProcessor\ApplicableCheckerInterface;
use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\ChainProcessor\ContextInterface;

class TraceableChainApplicableChecker extends ChainApplicableChecker
{
    /** @var TraceLogger */
    protected $logger;

    /**
     * @param TraceLogger $logger
     */
    public function __construct(TraceLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeChecker(
        ApplicableCheckerInterface $checker,
        ContextInterface $context,
        array $processorAttributes
    ) {
        $this->logger->startApplicableChecker(get_class($checker));
        $result = $checker->isApplicable($context, $processorAttributes);
        $this->logger->stopApplicableChecker();

        return $result;
    }
}
