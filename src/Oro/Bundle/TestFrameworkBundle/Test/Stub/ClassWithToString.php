<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\Stub;

/**
 * Stub class with a configurable string representation for testing.
 *
 * This stub allows tests to verify behavior with objects that have custom `__toString()`
 * implementations, with a configurable string representation for flexible testing scenarios.
 */
class ClassWithToString
{
    /** @var string */
    private $representation;

    /**
     * @param string $representation
     */
    public function __construct($representation = 'string representation')
    {
        $this->representation = $representation;
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->representation;
    }
}
