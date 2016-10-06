<?php

namespace Oro\Component\Log;

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
