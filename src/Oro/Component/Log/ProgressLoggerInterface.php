<?php

namespace Oro\Component\Log;

/**
 * @deprecated Will be removed in 2.0
 */
interface ProgressLoggerInterface
{
    /**
     * @param int $step
     */
    public function logSteps($step);

    /**
     * @param int $steps
     */
    public function logAdvance($steps);

    public function logFinish();
}
