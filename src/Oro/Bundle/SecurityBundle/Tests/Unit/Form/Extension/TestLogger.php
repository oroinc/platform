<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\Extension;

use Symfony\Component\HttpKernel\Tests\Logger;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

class TestLogger extends Logger implements DebugLoggerInterface
{
    public function countErrors()
    {
        return count($this->logs['error']);
    }
}
