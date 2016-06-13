<?php

namespace Oro\Component\ChainProcessor\Debug;

use Oro\Component\ChainProcessor\ProcessorBag;

class TraceableProcessorBag extends ProcessorBag
{
    /** @var TraceLogger */
    protected $logger;

    /**
     * @param TraceLogger $logger
     */
    public function setTraceLogger(TraceLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function createProcessorApplicableChecker()
    {
        return new TraceableChainApplicableChecker($this->logger);
    }
}
