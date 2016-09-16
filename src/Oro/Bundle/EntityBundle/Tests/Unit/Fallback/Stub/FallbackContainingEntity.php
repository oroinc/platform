<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Fallback\Stub;

class FallbackContainingEntity
{
    /**
     * @var mixed|null
     */
    public $testProperty;

    /**
     * @var mixed|null
     */
    public $testProperty2;

    /**
     * @param mixed $testProperty
     * @param mixed $testProperty2
     */
    public function __construct($testProperty = null, $testProperty2 = null)
    {
        $this->testProperty = $testProperty;
        $this->testProperty2 = $testProperty2;
    }
}
