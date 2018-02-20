<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\Extension;

use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Symfony\Component\HttpKernel\Tests\Logger;

class TestLogger extends Logger implements DebugLoggerInterface
{
    public function countErrors()
    {
        return count($this->logs['error']);
    }
}
