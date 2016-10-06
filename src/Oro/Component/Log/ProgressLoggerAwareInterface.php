<?php

namespace Oro\Component\Log;

interface ProgressLoggerAwareInterface
{
    /**
     * @param ProgressLoggerInterface $progressLogger
     */
    public function setProgressLogger(ProgressLoggerInterface $progressLogger);
}
