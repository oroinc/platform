<?php

namespace Oro\Component\Log;

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
