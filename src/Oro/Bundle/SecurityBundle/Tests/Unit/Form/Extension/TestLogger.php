<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\Extension;

use Psr\Log\Test\TestLogger as BaseTestLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

class TestLogger extends BaseTestLogger implements DebugLoggerInterface
{
    /**
     * {@inheritdoc}
     */
    public function countErrors(Request $request = null)
    {
        return count($this->recordsByLevel['error'] ?? []);
    }

    /**
     * {@inheritdoc}
     */
    public function getLogs(Request $request = null)
    {
        return $this->records;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->reset();
    }
}
