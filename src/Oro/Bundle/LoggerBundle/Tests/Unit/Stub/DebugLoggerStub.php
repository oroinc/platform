<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Stub;

use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

/**
 * This class is needed to avoid deprection notice which it triggered while using mock for DebugLoggerInterface
 */
class DebugLoggerStub implements DebugLoggerInterface
{
    /**
     * {@inheritdoc}
     */
    public function countErrors()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogs()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return;
    }
}
