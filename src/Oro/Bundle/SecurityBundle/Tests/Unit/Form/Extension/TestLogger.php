<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\Extension;

use Psr\Log\Test\TestLogger as BaseTestLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

class TestLogger extends BaseTestLogger implements DebugLoggerInterface
{
    /**
     * {@inheritDoc}
     */
    public function countErrors(Request $request = null)
    {
        return count($this->recordsByLevel['error'] ?? []);
    }

    /**
     * {@inheritDoc}
     */
    public function getLogs(Request $request = null)
    {
        return $this->records;
    }

    /**
     * {@inheritDoc}
     */
    public function clear()
    {
        $this->reset();
    }
}
