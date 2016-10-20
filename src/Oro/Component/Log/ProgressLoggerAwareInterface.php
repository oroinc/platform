<?php

namespace Oro\Component\Log;

/**
 * @deprecated Will be removed in 2.0
 */
interface ProgressLoggerAwareInterface
{
    /**
     * @param ProgressLoggerInterface $progressLogger
     */
    public function setProgressLogger(ProgressLoggerInterface $progressLogger);
}
