<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\Extension;

use Psr\Log\Test\TestLogger as BaseTestLogger;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

class TestLogger extends BaseTestLogger implements DebugLoggerInterface
{
    /**
     * {@inheritdoc}
     */
    public function countErrors()
    {
        return count($this->recordsByLevel['error'] ?? []);
    }

    /**
     * {@inheritdoc}
     */
    public function getLogs()
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
