<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture;

class CallableClass
{
    protected $returnValue;

    public function __construct($returnValue)
    {
        $this->returnValue = $returnValue;
    }

    public function __invoke()
    {
        return $this->returnValue;
    }
}
