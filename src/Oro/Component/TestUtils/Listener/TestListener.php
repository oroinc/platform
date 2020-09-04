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
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function endTest(Test $test, float $time): void
    {
        $reflection = new \ReflectionObject($test);

        foreach ($reflection->getProperties() as $property) {
            /** @noinspection NullPointerExceptionInspection */
            if ($property->isStatic()
                || 0 === \strpos($property->getDeclaringClass()->getName(), 'PHPUnit_')
                || ($property->hasType() && !$property->getType()->allowsNull())
            ) {
                continue;
            }
            $property->setAccessible(true);
            $property->setValue($test, null);
        }
    }
}
