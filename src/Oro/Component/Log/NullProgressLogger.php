<?php

namespace Oro\Component\Log;

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
