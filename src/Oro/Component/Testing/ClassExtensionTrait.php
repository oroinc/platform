<?php

namespace Oro\Component\Testing;

/**
 * Inheritance testing
 */
trait ClassExtensionTrait
{
    public function assertClassExtends($expected, $actual)
    {
        $this->assertTrue(
            is_a($actual, $expected, true),
            sprintf('Failed assert that class %s extends %s class.', $actual, $expected)
        );
    }

    public function assertClassImplements($expected, $actual)
    {
        $this->assertTrue(
            is_a($actual, $expected, true),
            sprintf('Failed assert that class %s implements %s interface.', $actual, $expected)
        );
    }
}
