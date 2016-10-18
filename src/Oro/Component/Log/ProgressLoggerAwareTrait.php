<?php

namespace Oro\Component\Log;

/**
 * @deprecated Will be removed in 2.0
 */
trait ProgressLoggerAwareTrait
{
    /** @var ProgressLoggerInterface */
    protected $progressLogger;

    /**
     * @param ProgressLoggerInterface $progressLogger
     */
    public function setProgressLogger(ProgressLoggerInterface $progressLogger)
    {
        $this->progressLogger = $progressLogger;
    }
}
