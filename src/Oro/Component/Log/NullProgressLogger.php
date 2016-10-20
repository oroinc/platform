<?php

namespace Oro\Component\Log;

/**
 * @deprecated Will be removed in 2.0
 */
class NullProgressLogger implements ProgressLoggerInterface
{
    /**
     * {@inheritdoc}
     */
    public function logAdvance($steps)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function logFinish()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function logSteps($step)
    {
    }
}
