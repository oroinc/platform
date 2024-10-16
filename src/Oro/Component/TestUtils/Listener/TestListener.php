<?php

namespace Oro\Component\TestUtils\Listener;

use Doctrine\Common\Annotations\AnnotationReader;
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
    #[\Override]
    public function endTest(Test $test, float $time): void
    {
        $reflection = new \ReflectionClass($test);

        $propertyNames = [];
        $properties = $reflection->getProperties();
        foreach ($properties as $property) {
            if ($property->isStatic()) {
                continue;
            }
            if (str_starts_with($property->getDeclaringClass()->getName(), 'PHPUnit\\')) {
                continue;
            }
            $propertyNames[] = $property->getName();
        }

        if ($propertyNames) {
            \Closure::bind(
                function (array $propertyNames) {
                    foreach ($propertyNames as $propertyName) {
                        unset($this->{$propertyName});
                    }
                },
                $test,
                $reflection->getName()
            )($propertyNames);
        }
    }

    #[\Override]
    public function startTest(Test $test): void
    {
        AnnotationReader::addGlobalIgnoredName('mixin');
    }
}
