<?php

namespace Oro\Component\TestUtils\Listener;

use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener as BaseListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;

/**
 * Speeds up PHPUnit tests by freeing memory.
 */
class TestListener implements BaseListener
{
    use TestListenerDefaultImplementation;

    /**
     * @param Test $test
     * @param float $time
     */
    public function endTest(Test $test, float $time): void
    {
        $reflection = new \ReflectionObject($test);

        foreach ($reflection->getProperties() as $property) {
            if ($property->isStatic() || strpos($property->getDeclaringClass()->getName(), 'PHPUnit_') === 0) {
                continue;
            }

            $property->setAccessible(true);
            $property->setValue($test, null);
        }
    }
}
