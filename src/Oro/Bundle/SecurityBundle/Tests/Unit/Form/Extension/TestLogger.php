<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\Extension;

use Psr\Log\Test\TestLogger as BaseTestLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

class TestLogger extends BaseTestLogger implements DebugLoggerInterface
{
    #[\Override]
    public function countErrors(?Request $request = null): int
    {
        return count($this->recordsByLevel['error'] ?? []);
    }

    #[\Override]
    public function getLogs(?Request $request = null): array
    {
        return $this->records;
    }

    #[\Override]
    public function clear()
    {
        $this->reset();
    }
}
