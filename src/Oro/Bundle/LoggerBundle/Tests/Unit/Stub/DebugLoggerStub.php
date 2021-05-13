<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Stub;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

/**
 * This class is needed to avoid deprecation notice which it triggered while using mock for DebugLoggerInterface
 */
class DebugLoggerStub implements DebugLoggerInterface
{
    /**
     * {@inheritdoc}
     */
    public function countErrors(Request $request = null)
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogs(Request $request = null)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
    }
}
