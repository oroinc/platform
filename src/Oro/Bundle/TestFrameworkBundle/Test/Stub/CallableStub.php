<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\Stub;

/**
 * Stub class that implements the callable interface for testing.
 *
 * This stub can be used as a callable in tests where a callable object is required,
 * providing a simple no-op implementation for testing purposes.
 */
class CallableStub
{
    public function __invoke()
    {
        return;
    }
}
